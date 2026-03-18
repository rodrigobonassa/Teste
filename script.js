const POLL_INTERVAL = 15000;
const STORAGE_KEY = 'monitoring-endpoint';

const fallbackData = {
  updatedAt: new Date().toISOString(),
  sites: [
    {
      name: 'ABC da Educação',
      checks: [
        { label: 'Webhook', status: 'ok' },
        { label: 'Ações pendentes', status: 'ok' },
        { label: 'Emails com falha', status: 'ok' },
      ],
    },
    {
      name: 'Prof. Decorativa',
      checks: [
        { label: 'Webhook', status: 'failure', message: 'Sem retorno há 5 min' },
        { label: 'Ações pendentes', status: 'ok' },
        { label: 'Emails com falha', status: 'ok' },
      ],
    },
  ],
};

const endpointInput = document.querySelector('#endpoint-input');
const saveEndpointButton = document.querySelector('#save-endpoint');
const testAlertButton = document.querySelector('#test-alert');
const sitesGrid = document.querySelector('#sites-grid');
const lastUpdate = document.querySelector('#last-update');
const activeFailures = document.querySelector('#active-failures');

let previousFailureKeys = new Set();
let audioContext;

function getEndpoint() {
  return localStorage.getItem(STORAGE_KEY) || '';
}

function setEndpoint(value) {
  localStorage.setItem(STORAGE_KEY, value);
}

function normalizeStatus(status) {
  return String(status).toLowerCase() === 'failure' ? 'failure' : 'ok';
}

function formatTimestamp(value) {
  if (!value) return 'sem informação';
  const date = new Date(value);
  return Number.isNaN(date.getTime()) ? 'data inválida' : date.toLocaleString('pt-BR');
}

function getFailureKeys(payload) {
  return new Set(
    payload.sites.flatMap((site) =>
      site.checks
        .filter((check) => normalizeStatus(check.status) === 'failure')
        .map((check) => `${site.name}:${check.label}`)
    )
  );
}

function playAlert() {
  const AudioCtx = window.AudioContext || window.webkitAudioContext;
  if (!AudioCtx) return;

  if (!audioContext) {
    audioContext = new AudioCtx();
  }

  if (audioContext.state === 'suspended') {
    audioContext.resume();
  }

  const oscillator = audioContext.createOscillator();
  const gain = audioContext.createGain();

  oscillator.type = 'square';
  oscillator.frequency.setValueAtTime(880, audioContext.currentTime);
  oscillator.frequency.setValueAtTime(660, audioContext.currentTime + 0.15);
  gain.gain.setValueAtTime(0.0001, audioContext.currentTime);
  gain.gain.exponentialRampToValueAtTime(0.18, audioContext.currentTime + 0.02);
  gain.gain.exponentialRampToValueAtTime(0.0001, audioContext.currentTime + 0.55);

  oscillator.connect(gain);
  gain.connect(audioContext.destination);
  oscillator.start();
  oscillator.stop(audioContext.currentTime + 0.58);
}

function render(payload) {
  const safePayload = {
    updatedAt: payload.updatedAt || new Date().toISOString(),
    sites: Array.isArray(payload.sites) ? payload.sites : [],
  };

  sitesGrid.innerHTML = '';

  safePayload.sites.forEach((site) => {
    const card = document.createElement('article');
    card.className = 'site-card';

    const checks = (Array.isArray(site.checks) ? site.checks : [])
      .map((check) => {
        const status = normalizeStatus(check.status);
        const label = status === 'failure' ? 'FALHA' : 'OK';
        const message = check.message ? `<div class="status-message">${check.message}</div>` : '';

        return `
          <div class="check-item">
            <span class="check-label">${check.label}</span>
            <div class="status-tile ${status}">
              <div class="status-value">${label}</div>
              ${message}
            </div>
          </div>
        `;
      })
      .join('');

    card.innerHTML = `
      <h2>${site.name}</h2>
      <div class="divider"></div>
      <div class="check-grid">${checks}</div>
    `;

    sitesGrid.appendChild(card);
  });

  lastUpdate.textContent = formatTimestamp(safePayload.updatedAt);

  const failureKeys = getFailureKeys(safePayload);
  activeFailures.textContent = String(failureKeys.size);

  const hasNewFailure = [...failureKeys].some((key) => !previousFailureKeys.has(key));
  if (hasNewFailure) {
    playAlert();
  }

  previousFailureKeys = failureKeys;
}

async function fetchStatus() {
  const endpoint = getEndpoint();

  if (!endpoint) {
    render(fallbackData);
    return;
  }

  try {
    const response = await fetch(endpoint, {
      headers: { Accept: 'application/json' },
      cache: 'no-store',
    });

    if (!response.ok) {
      throw new Error(`HTTP ${response.status}`);
    }

    const payload = await response.json();
    render(payload);
  } catch (error) {
    console.error('Erro ao consultar endpoint do n8n:', error);
    render({
      updatedAt: new Date().toISOString(),
      sites: fallbackData.sites.map((site) => ({
        ...site,
        checks: site.checks.map((check) =>
          check.label === 'Webhook'
            ? { ...check, status: 'failure', message: 'Falha ao consultar o endpoint configurado' }
            : check
        ),
      })),
    });
  }
}

saveEndpointButton.addEventListener('click', () => {
  setEndpoint(endpointInput.value.trim());
  fetchStatus();
});

testAlertButton.addEventListener('click', playAlert);

endpointInput.value = getEndpoint();
render(fallbackData);
fetchStatus();
window.setInterval(fetchStatus, POLL_INTERVAL);

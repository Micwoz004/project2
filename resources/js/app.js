const mapCanvas = document.querySelector('[data-map-canvas]');
const mapData = document.querySelector('#map-projects-data');

if (mapCanvas instanceof HTMLElement && mapData instanceof HTMLScriptElement) {
    try {
        const projects = JSON.parse(mapData.textContent || '[]');

        projects.slice(0, 24).forEach((project, index) => {
            const pin = document.createElement('a');
            const x = 10 + ((index * 23) % 78);
            const y = 14 + ((index * 31) % 68);

            pin.className = 'map-pin';
            pin.href = project.url;
            pin.style.left = `${x}%`;
            pin.style.top = `${y}%`;
            pin.setAttribute('aria-label', project.title);
            pin.innerHTML = `<span>${index + 1}</span>`;

            mapCanvas.appendChild(pin);
        });
    } catch {
        mapCanvas.hidden = true;
    }
}

const trackSelector = '[data-staff-carousel-track]';

function updateControls(track) {
    const controls = track.parentElement.querySelector('[data-staff-carousel-controls]');
    const last = track.scrollWidth - track.clientWidth;
    const current = Math.abs(track.scrollLeft);
    controls.hidden = last <= 1;
    track.tabIndex = controls.hidden ? -1 : 0;
    controls.querySelector('[data-staff-carousel-direction="-1"]').setAttribute('aria-disabled', String(current <= 1));
    controls.querySelector('[data-staff-carousel-direction="1"]').setAttribute('aria-disabled', String(current >= last - 1));
}

function refreshCarousels() {
    document.querySelectorAll(trackSelector).forEach(updateControls);
}

function scrollCarousel(track, direction) {
    const forward = getComputedStyle(track).direction === 'rtl' ? -1 : 1;
    track.scrollBy({ left: direction * track.clientWidth * forward });
}

document.addEventListener('click', (event) => {
    const button = event.target.closest('[data-staff-carousel-direction]');
    if (button && button.getAttribute('aria-disabled') !== 'true') {
        const track = document.getElementById(button.getAttribute('aria-controls'));
        scrollCarousel(track, Number(button.dataset.staffCarouselDirection));
    }
});

document.addEventListener('keydown', (event) => {
    const track = event.target;
    if (!(track instanceof Element) || !track.matches(trackSelector) || event.altKey || event.ctrlKey || event.metaKey || event.shiftKey
        || !['ArrowLeft', 'ArrowRight'].includes(event.key)) {
        return;
    }
    event.preventDefault();
    track.scrollBy({ left: event.key === 'ArrowLeft' ? -track.clientWidth : track.clientWidth });
});

document.addEventListener('scroll', (event) => {
    if (event.target instanceof Element && event.target.matches(trackSelector)) {
        updateControls(event.target);
    }
}, true);

document.addEventListener('kraft:content-added', refreshCarousels);
window.addEventListener('resize', refreshCarousels);
refreshCarousels();

/**
 * Show a tooltip on hover and focus
 *
 * @param {string} [text="hover me"] - The text to show.
 * @returns {import('svelte/attachments').Attachment}
 */
export function tooltip(text = "hover me") {
    return (/** @type {HTMLElement} */ element) => {
        let tooltipEl;
        let showTimeout;
        let isActive = false;

        function createTooltip() {
            if (tooltipEl) return;

            tooltipEl = document.createElement('div');
            tooltipEl.textContent = text;
            tooltipEl.className = "lighter-tooltip";
            document.body.appendChild(tooltipEl);

            const rect = element.getBoundingClientRect();
            const scrollY = window.scrollY || document.documentElement.scrollTop;
            const scrollX = window.scrollX || document.documentElement.scrollLeft;

            tooltipEl.style.top = rect.top + scrollY - 40 + "px";
            tooltipEl.style.left = rect.left + scrollX + rect.width / 2 + "px";
        }

        function show() {
            isActive = true;
            showTimeout = setTimeout(() => {
                if (isActive) createTooltip();
            }, 500);
        }

        function hide() {
            isActive = false;
            tooltipEl?.remove();
            tooltipEl = null;
            clearTimeout(showTimeout);
            showTimeout = null;
        }

        element.addEventListener("mouseenter", show)
        element.addEventListener("mouseleave", hide)
        element.addEventListener("focus", show);
        element.addEventListener("blur", hide);

        return () => {
            hide();
            element.removeEventListener("mouseenter", show)
            element.removeEventListener("mouseleave", hide)
            element.removeEventListener("focus", show)
            element.removeEventListener("blur", hide)
        };
    };
}

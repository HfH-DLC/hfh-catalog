(() => {
  const initSwitcher = () => {
    const switcher = document.querySelector(".hfh-catalog__switcher");
    if (!switcher) {
      return;
    }

    const toggle = switcher.querySelector("summary");
    const close = () => {
      switcher.open = false;
    };

    switcher.classList.add("hfh-catalog__switcher--overlay");

    switcher.addEventListener("keydown", (event) => {
      if (event.key === "Escape" && switcher.open) {
        close();
        toggle.focus();
      }
    });

    switcher.addEventListener("focusout", (event) => {
      if (event.relatedTarget && !switcher.contains(event.relatedTarget)) {
        close();
      }
    });

    document.addEventListener("click", (event) => {
      if (switcher.open && !switcher.contains(event.target)) {
        close();
      }
    });
  };

  const initFilters = () => {
    const config = window.hfhCatalog;
    const form = document.querySelector(".hfh-catalog__filters");
    const results = document.querySelector(".hfh-catalog__results");
    const status = document.querySelector(".hfh-catalog__status");

    if (!config || !form || !results) {
      return;
    }

    const submit = form.querySelector('[type="submit"]');
    if (submit) {
      submit.hidden = true;
    }

    let controller = null;
    let announcement = null;

    const announce = (message) => {
      if (!status) {
        return;
      }

      window.clearTimeout(announcement);
      status.textContent = "";
      announcement = window.setTimeout(() => {
        status.textContent = message;
      }, 100);
    };

    const update = async () => {
      const query = new URLSearchParams(new FormData(form)).toString();

      controller?.abort();
      controller = new AbortController();

      try {
        const response = await fetch(
          config.endpoint + (query ? "?" + query : ""),
          { signal: controller.signal },
        );
        if (!response.ok) {
          throw new Error(response.status);
        }

        const data = await response.json();
        results.innerHTML = data.html;
        announce(data.status);
        history.replaceState(
          null,
          "",
          query ? config.pageUrl + "?" + query : config.pageUrl,
        );
      } catch (error) {
        if (error.name !== "AbortError") {
          form.submit();
        }
      }
    };

    form.addEventListener("change", update);
  };

  initSwitcher();
  initFilters();
})();

(() => {
  const LIST_VIEW = "list";

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

  const initResults = () => {
    const config = window.hfhCatalog;
    const results = document.querySelector(".hfh-catalog__results");

    if (!config || !results) {
      return;
    }

    const form = document.querySelector(".hfh-catalog__filters");
    const status = document.querySelector(".hfh-catalog__status");
    const viewInput = form?.querySelector(".hfh-catalog__view-input");
    const viewLinks = [...document.querySelectorAll(".hfh-catalog__view")];

    let view = viewLinks.find((link) => link.hasAttribute("aria-current"))
      ?.dataset.view;

    const pageUrlFor = (query) =>
      query ? config.pageUrl + "?" + query : config.pageUrl;

    const currentViewLink = () =>
      viewLinks.find((link) => link.dataset.view === view);

    const applyView = () => {
      results
        .querySelector(".hfh-catalog__list")
        ?.classList.toggle("hfh-catalog__list--list", view === LIST_VIEW);

      viewLinks.forEach((link) => {
        if (link.dataset.view === view) {
          link.setAttribute("aria-current", "true");
        } else {
          link.removeAttribute("aria-current");
        }
      });

      if (viewInput) {
        viewInput.disabled = view !== LIST_VIEW;
      }
    };

    const refreshViewLinks = () => {
      if (!viewInput) {
        return;
      }

      const disabled = viewInput.disabled;

      viewLinks.forEach((link) => {
        viewInput.disabled = link.dataset.view !== LIST_VIEW;
        link.href = pageUrlFor(
          new URLSearchParams(new FormData(form)).toString(),
        );
      });

      viewInput.disabled = disabled;
    };

    viewLinks.forEach((link) => {
      link.addEventListener("click", (event) => {
        if (
          event.button !== 0 ||
          event.metaKey ||
          event.ctrlKey ||
          event.shiftKey ||
          event.altKey
        ) {
          return;
        }

        event.preventDefault();
        view = link.dataset.view;
        applyView();
        history.replaceState(null, "", link.href);
      });
    });

    if (!form) {
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
        applyView();
        refreshViewLinks();
        announce(data.status);
        history.replaceState(
          null,
          "",
          currentViewLink()?.href ?? pageUrlFor(query),
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
  initResults();
})();

(() => {
  const config = window.hfhCatalog;
  const form = document.querySelector(".hfh-catalog__filters");
  const results = document.querySelector(".hfh-catalog__results");

  if (!config || !form || !results) {
    return;
  }

  const submit = form.querySelector('[type="submit"]');
  if (submit) {
    submit.hidden = true;
  }

  let controller = null;

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
})();

(function ($) {
  "use strict";

  const config = window.hfhCatalogSortable;
  const ROW_ID_PREFIX = "hfh-row-";
  const ITEM = "tr.hfh-sortable__item";
  const MOVE_UP_LINK = ".row-actions .move_up > a";
  const MOVE_DOWN_LINK = ".row-actions .move_down > a";

  if (!config) {
    return;
  }

  /**
   * A dragged table row loses its cell widths, so the clone keeps the original's.
   */
  function keepCellWidths(event, $row) {
    const $cells = $row.children();
    const $helper = $row.clone();
    $helper.children().each(function (index) {
      $(this).width($cells.eq(index).width());
    });
    return $helper;
  }

  function updateMarkers($body) {
    const $items = $body.children(ITEM).not(".ui-sortable-helper");
    $items.removeClass("hfh-sortable__item--first hfh-sortable__item--last");
    $items.first().addClass("hfh-sortable__item--first");
    $items.last().addClass("hfh-sortable__item--last");
  }

  async function saveOrder(type, parent, order) {
    const response = await fetch(config.restUrl, {
      method: "POST",
      credentials: "same-origin",
      headers: {
        "Content-Type": "application/json",
        "X-WP-Nonce": config.nonce,
      },
      body: JSON.stringify({ type, parent, order }),
    }).catch(() => null);

    if (!response || !response.ok) {
      const error = response ? await response.json().catch(() => ({})) : {};
      throw new Error(error.message || config.errorMessage);
    }
  }

  $(".hfh-sortable").each(function () {
    const $wrapper = $(this);
    const $body = $wrapper.find("tbody").first();
    const type = $wrapper.attr("data-type");
    const parent = Number($wrapper.attr("data-parent"));
    let busy = false;

    function persist() {
      const prefix = `${ROW_ID_PREFIX}${type}-`;
      const order = $body
        .sortable("toArray")
        .map((id) => parseInt(id.slice(prefix.length), 10));

      busy = true;
      $body.sortable("disable");

      return saveOrder(type, parent, order)
        .then(() => {
          updateMarkers($body);
          $body.sortable("enable");
          busy = false;
        })
        .catch((error) => {
          window.alert(error.message);
          window.location.reload();
        });
    }

    $body.sortable({
      items: "> " + ITEM,
      axis: "y",
      cancel: "a, input, button, label",
      helper: keepCellWidths,
      placeholder: "hfh-sortable__placeholder",
      forcePlaceholderSize: true,
      update: persist,
    });

    $body.on(
      "click",
      `${ITEM} ${MOVE_UP_LINK}, ${ITEM} ${MOVE_DOWN_LINK}`,
      function (event) {
        event.preventDefault();

        if (busy) {
          return;
        }

        const $link = $(this);
        const $row = $link.closest(ITEM);
        const up = $link.is(MOVE_UP_LINK);
        const $target = up
          ? $row.prevAll(ITEM).first()
          : $row.nextAll(ITEM).first();

        if (!$target.length) {
          return;
        }

        if (up) {
          $row.insertBefore($target);
        } else {
          $row.insertAfter($target);
        }
        $link.trigger("focus");

        persist().then(() => {
          if (!$link.is(":visible")) {
            $row.find(up ? MOVE_DOWN_LINK : MOVE_UP_LINK).trigger("focus");
          }
        });
      },
    );
  });
})(jQuery);

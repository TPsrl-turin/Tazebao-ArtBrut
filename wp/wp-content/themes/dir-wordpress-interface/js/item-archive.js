jQuery(function ($) {
	
	$(function() {
	  var $sel = $('.filter-wrapper select');

	  // funzione per gestire la classe in base al valore
	  function checkValue() {
		$(this).toggleClass('has-value', !!$(this).val());
	  }

	  // al caricamento pagina
	  $sel.each(checkValue);

	  // ad ogni cambio di selezione
	  $sel.on('change', checkValue);
	});
	
  const splideAvailable = typeof Splide !== "undefined";

  /* DOM */
  // Use class selector to find the grid. If multiple exist, this logic currently only supports the first one found.
  // Future improvement: Scope filters and pagination to each grid instance.
  const $wrapper = $(".brut-item-archive-wrapper").first();
  const $grid = $wrapper.find(".item-archive-grid");
  const $pagi = $wrapper.find(".brut-item-pagination");
  const $main = $wrapper.find(".brut-item-splide-main");
  const $thumb = $wrapper.find(".brut-item-splide-thumb");

  /* stato */
  const filters = {
    page: 1,
    mu: "",
    au: "",
    tg: "",
    hp: "",
    pp: "",
    ma: "",
    tq: "",
    search: "",
    // Initial read from DOM
    my_collection: $grid.attr('data-my-collection') === '1' ? 1 : 0,
    per_page: $grid.attr('data-per-page') || 24,
  };
  let view = "grid"; // grid | slider | row
  let maxPg = 1;
  let loaded = 0;
  let spMain = null,
    spThumb = null;

  /* helper class body */
  function bodyClass() {
    $("body")
      .removeClass("slider-view row-view")
      .toggleClass("slider-view", view === "slider")
      .toggleClass("row-view", view === "row");
  }

  /* helper URL */
  function pushURL() {
    const q = new URLSearchParams();
    if (filters.hp) q.set("hp", filters.hp);
    if (filters.pp) q.set("pp", filters.pp);
    if (filters.ma) q.set("ma", filters.ma);
    if (filters.tq) q.set("tq", filters.tq);
    if (filters.page > 1) q.set("pg", filters.page);
    if (filters.mu) q.set("mu", filters.mu);
    if (filters.au) q.set("au", filters.au);
    if (filters.tg) q.set("tg", filters.tg);
    if (filters.search) q.set("q", filters.search.trim());
    if (view !== "grid") q.set("v", view);
    
    // Preserve hash
    const hash = location.hash;
    history.replaceState(
      null,
      "",
      (q.toString() ? "?" + q.toString() : location.pathname) + hash
    );
  }

  /* ---- Splide on/off ------------------------------------------------ */
  function initSplide(mainHTML, thumbHTML) {
    if (!$main.length || !$thumb.length) return;

    $main.find(".splide__track > .splide__list").html(mainHTML);
    $thumb.find(".splide__track > .splide__list").html(thumbHTML);
    loaded = $(mainHTML).length;

    spThumb = new Splide($thumb[0], {
      fixedWidth: 120,
      gap: 8,
      pagination: false,
      isNavigation: true,
      arrows: false,
      focus: "center",
      rewind: false,
    }).mount();

    spMain = new Splide($main[0], {
      type: "slide",
      perPage: 1,
      pagination: false,
      arrows: false,
      rewind: false,
    })
      .sync(spThumb)
      .mount();

    spMain.on("moved", (i) => {
      if (i >= loaded - 3 && filters.page < maxPg) {
        filters.page++;
        ajaxLoad(true);
      }
    });
  }

  function destroySplide() {
    if (spMain) {
      spMain.destroy();
      spThumb.destroy();
    }
    spMain = spThumb = null;
    loaded = 0;
  }

  /* ---- AJAX --------------------------------------------------------- */
  function ajaxLoad(onlySlides = false) {
    console.log('TP Debug: ajaxLoad called', { onlySlides, filters }); // Debug
    
    // Always get the current grid from DOM to avoid stale references
    const $currentGrid = $wrapper.find(".item-archive-grid");
    const $currentPagi = $wrapper.find(".brut-item-pagination");
    
    if (!onlySlides) {
      $currentGrid.html("<p>" + ItemArchive.texts.loading + "</p>");
      if (view === "slider") {
        $main.hide();
        $thumb.hide();
      }
    }

    $.post(
      ItemArchive.ajax_url,
      {
        action: "tp_filter_items_v2",
        nonce: ItemArchive.nonce,
        only_slides: onlySlides ? 1 : 0,
        page: filters.page,
        museum: filters.mu,
        author: filters.au,
        tag: filters.tg,
        hp: filters.hp,
        pp: filters.pp,
        ma: filters.ma,
        tq: filters.tq,
        search: filters.search,
        // Read directly from DOM to ensure persistence
        my_collection: $currentGrid.attr('data-my-collection') === '1' ? 1 : 0,
        per_page: $currentGrid.attr('data-per-page') || 24,
      }
    ).done((res) => {
      if (!res.success) return;

      maxPg = res.data.max_pages || 1;

      if (view === "slider" && splideAvailable) {
        if (onlySlides) {
          /* aggiunta dinamica */
          $(res.data.main).each((_, li) => spMain.add(li));
          $(res.data.thumbs).each((_, li) => spThumb.add(li));
          loaded += $(res.data.main).length;
        } else {
          destroySplide();
          initSplide(res.data.main, res.data.thumbs);
          $currentGrid.empty().hide();
          $main.show();
          $thumb.show();
        }
        $currentPagi.empty();
      } else {
        /* grid | row */
        destroySplide();
        $main.hide();
        $thumb.hide();
        $currentGrid.show().html(res.data.html);
        $currentPagi.html(res.data.pagination);
      }

      pushURL();
      bodyClass();
    });
  }

  /* ---- filtri ---- */
  $wrapper.find(
    "#filter-hp, #filter-pp, #filter-ma, #filter-tq, #filter-museum, #filter-author, #filter-tag"
  ).on("change", function () {
    const map = {
      "filter-hp": "hp",
      "filter-pp": "pp",
      "filter-ma": "ma",
      "filter-tq": "tq",
      "filter-museum": "mu",
      "filter-author": "au",
      "filter-tag": "tg",
    };
    filters[map[this.id]] = this.value;
    filters.page = 1;
    ajaxLoad(false);
  });

  let deb;
  $wrapper.find("#filter-search").on("input", function () {
    clearTimeout(deb);
    const v = this.value;
    deb = setTimeout(() => {
      filters.search = v;
      filters.page = 1;
      ajaxLoad(false);
    }, 400);
  });

  /* paginazione */
  $(document).on("click", ".brut-item-pagination a", function (e) {
    e.preventDefault();
    const href = $(this).attr("href");
    // Support pg, paged, and /page/N/
    const m = href.match(/(?:[?&](?:pg|paged)=|\/page\/)(\d+)/);
    const p = m ? parseInt(m[1], 10) : parseInt($(this).text(), 10);
    filters.page = isNaN(p) || p < 1 ? 1 : p;
    ajaxLoad(false);
  });

  /* view switch */
  $wrapper.find(".view-wrapper button").on("click", function () {
    $wrapper.find(".view-wrapper button").removeClass("is-active");
    $(this).addClass("is-active");
    const req = this.id.replace("-view", ""); // grid | slider | row
    if (req === "slider" && !splideAvailable) {
      alert("Slider non disponibile: Splide non caricato");
      $("#grid-view").addClass("is-active");
      return;
    }
    view = req;
    filters.page = 1;
    ajaxLoad(false);
  });

  /* ---- bootstrap da URL ---- */
  (function () {
    const qs = new URLSearchParams(location.search);

    if (qs.get("pg")) filters.page = parseInt(qs.get("pg"), 10) || 1;
    if (qs.get("paged")) filters.page = parseInt(qs.get("paged"), 10) || 1;
    if (qs.get("hp")) filters.hp = qs.get("hp");
    if (qs.get("pp")) filters.pp = qs.get("pp");
    if (qs.get("ma")) filters.ma = qs.get("ma");
    if (qs.get("tq")) filters.tq = qs.get("tq");
    if (qs.get("mu")) filters.mu = qs.get("mu");
    if (qs.get("au")) filters.au = qs.get("au");
    if (qs.get("tg")) filters.tg = qs.get("tg");
    if (qs.get("q")) filters.search = qs.get("q");

    const v = qs.get("v") || "grid";
    view = v === "slider" && !splideAvailable ? "grid" : v;

    $wrapper.find("#filter-hp").val(filters.hp);
    $wrapper.find("#filter-pp").val(filters.pp);
    $wrapper.find("#filter-ma").val(filters.ma);
    $wrapper.find("#filter-tq").val(filters.tq);
    $wrapper.find("#filter-museum").val(filters.mu);
    $wrapper.find("#filter-author").val(filters.au);
    $wrapper.find("#filter-tag").val(filters.tg);
    $wrapper.find("#filter-search").val(filters.search);
    $wrapper.find(".view-wrapper button").removeClass("is-active");
    $wrapper.find("#" + view + "-view").addClass("is-active");
    bodyClass();
  })();

  /* ---- primo load ---- */
  ajaxLoad(false);
});

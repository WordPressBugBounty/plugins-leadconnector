(function () {
  "use strict";

  var highlightEnabled = true;

  /* Throttle helper to limit event frequency */
  function throttle(func, limit) {
    var inThrottle;
    return function () {
      var args = arguments;
      var context = this;
      if (!inThrottle) {
        func.apply(context, args);
        inThrottle = true;
        setTimeout(function () {
          inThrottle = false;
        }, limit);
      }
    };
  }

  /* Returns trimmed text content of an element */
  function getFullText(element) {
    return (element.textContent || element.innerText || "").trim();
  }

  /* Relay an event to the parent LeadConnector window */
  function sendToParent(eventType, element, elementType) {
    try {
      if (window.parent && window.parent !== window) {
        var rect = element.getBoundingClientRect();
        var elementData = {
          type: eventType,
          elementType: elementType,
          selector: element.className
            ? element.className.split(" ").find(function (cls) {
                return (
                  cls.includes("hero-heading-class") ||
                  cls.includes("hero-description-class") ||
                  cls.includes("about-us-story-header-class") ||
                  cls.includes("about-us-story-content-class")
                );
              }) || ""
            : "",
          classes: element.className || "",
          position: {
            x: Math.round(rect.left),
            y: Math.round(rect.top),
            width: Math.round(rect.width),
            height: Math.round(rect.height),
          },
          text:
            elementType === "heading" ||
            elementType === "description" ||
            elementType === "about-us-story-header" ||
            elementType === "about-us-story-content"
              ? getFullText(element)
              : null,
        };
        window.parent.postMessage(
          Object.assign(
            { source: "leadconnector-elementor-iframe" },
            elementData,
          ),
          "*",
        );
      }
    } catch (e) {
      console.warn("LeadConnector: Could not send message to parent", e);
    }
  }

  var sendClick = function (element, elementType) {
    if (!highlightEnabled) return;
    sendToParent("elementor-click", element, elementType);
  };

  /* WeakSet tracks elements that already have listeners to prevent duplicates */
  var elementsWithListeners = new WeakSet();

  /* Map element class to a semantic type string */
  function getElementType(element) {
    if (element.classList.contains("hero-heading-class")) return "heading";
    if (element.classList.contains("hero-description-class"))
      return "description";
    if (element.classList.contains("about-us-story-header-class"))
      return "about-us-story-header";
    if (element.classList.contains("about-us-story-content-class"))
      return "about-us-story-content";
    return null;
  }

  function initEventListeners() {
    /* Text / heading widgets — click → relay to parent */
    var customElements = document.querySelectorAll(
      ".hero-heading-class, .hero-description-class, .about-us-story-content-class, .about-us-story-header-class",
    );

    customElements.forEach(function (element) {
      if (elementsWithListeners.has(element)) return;

      var clickHandler = function (e) {
        var clickedElement = e.target;
        if (!element.contains(clickedElement) && clickedElement !== element)
          return;
        var elementType = getElementType(element);
        if (elementType) sendClick(element, elementType);
      };

      element.addEventListener("click", clickHandler, {
        passive: true,
        capture: true,
      });
      element._leadConnectorClickHandler = clickHandler;
      elementsWithListeners.add(element);
    });

    /* Image widgets — inject "Regenerate Image" overlay */
    var imageElements = document.querySelectorAll(
      ".hero-image-class, .about-us-image-class",
    );
    imageElements.forEach(function (element) {
      if (elementsWithListeners.has(element)) return;

      var img = element.querySelector("img");
      if (!img) {
        elementsWithListeners.add(element);
        return;
      }

      if (window.getComputedStyle(element).position === "static") {
        element.style.position = "relative";
      }

      var overlay = document.createElement("button");
      overlay.className = "leadconnector-regenerate-image-overlay";
      if (!highlightEnabled) overlay.style.display = "none";
      overlay.innerHTML =
        '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#7c3aed" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;margin-right:6px;vertical-align:middle;display:inline-block"><path d="m21.64 3.64-1.28-1.28a1.21 1.21 0 0 0-1.72 0L2.36 18.64a1.21 1.21 0 0 0 0 1.72l1.28 1.28a1.2 1.2 0 0 0 1.72 0L21.64 5.36a1.2 1.2 0 0 0 0-1.72"/><path d="m14 7 3 3"/><path d="M5 6v4"/><path d="M19 14v4"/><path d="M10 2v2"/><path d="M7 8H3"/><path d="M21 16h-4"/><path d="M11 3H9"/></svg>Regenerate Image';
      element.appendChild(overlay);

      function positionOverlay() {
        var imgRect = img.getBoundingClientRect();
        var parentRect = element.getBoundingClientRect();
        overlay.style.left =
          imgRect.left - parentRect.left + imgRect.width / 2 + "px";
        overlay.style.top =
          imgRect.top - parentRect.top + imgRect.height / 2 + "px";
        overlay.style.transform = "translate(-50%, -50%)";
      }

      element.addEventListener("mouseenter", positionOverlay);
      if (!img.complete) img.addEventListener("load", positionOverlay);
      positionOverlay();

      var elementType = element.classList.contains("hero-image-class")
        ? "hero-image-class"
        : element.classList.contains("about-us-image-class")
          ? "about-us-image-class"
          : "";

      overlay.addEventListener(
        "click",
        function (e) {
          e.preventDefault();
          e.stopPropagation();
          if (!highlightEnabled) return;

          var imgEl = element.querySelector("img");
          var imgSrc = imgEl ? imgEl.getAttribute("src") || "" : "";
          var naturalW = imgEl ? imgEl.naturalWidth : 0;
          var naturalH = imgEl ? imgEl.naturalHeight : 0;

          try {
            if (window.parent && window.parent !== window) {
              window.parent.postMessage(
                {
                  source: "leadconnector-elementor-iframe",
                  type: "image-click",
                  elementType: elementType,
                  imageUrl: imgSrc,
                  dimensions: { width: naturalW, height: naturalH },
                },
                "*",
              );
            }
          } catch (err) {
            console.warn(
              "LeadConnector: Could not send image-click to parent",
              err,
            );
          }
        },
        { capture: true },
      );

      elementsWithListeners.add(element);
    });
  }

  /* Initialize after DOM is ready; retry after a delay for dynamically-rendered Elementor widgets */
  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initEventListeners);
  } else {
    setTimeout(initEventListeners, 500);
  }
  setTimeout(initEventListeners, 1500);

  /* -----------------------------------------------------------------------
   * Message handler — receives commands from the parent LeadConnector app
   * ----------------------------------------------------------------------- */
  function normalizeHex(value) {
    if (!value || typeof value !== "string") return "";
    value = value.trim();
    var m = value.match(/^#([0-9A-Fa-f]{6})$/);
    if (m) return "#" + m[1].toLowerCase();
    var m8 = value.match(/^#([0-9A-Fa-f]{6})([0-9A-Fa-f]{2})$/);
    if (m8) return "#" + m8[1].toLowerCase();
    var rgb = value.match(/^rgb\s*\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)\s*\)$/);
    if (rgb) {
      var r = parseInt(rgb[1], 10);
      var g = parseInt(rgb[2], 10);
      var b = parseInt(rgb[3], 10);
      return (
        "#" +
        [r, g, b]
          .map(function (n) {
            var h = n.toString(16);
            return h.length === 1 ? "0" + h : h;
          })
          .join("")
      );
    }
    var rgba = value.match(
      /^rgba\s*\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)\s*,\s*[\d.]+\s*\)$/,
    );
    if (rgba) {
      var r2 = parseInt(rgba[1], 10);
      var g2 = parseInt(rgba[2], 10);
      var b2 = parseInt(rgba[3], 10);
      return (
        "#" +
        [r2, g2, b2]
          .map(function (n) {
            var h = n.toString(16);
            return h.length === 1 ? "0" + h : h;
          })
          .join("")
      );
    }
    return value;
  }

  var COLOR_KEYS = [
    "primary",
    "secondary",
    "accent",
    "light_neutral",
    "light_neutral_text",
  ];

  function escapeRegex(str) {
    return str.replace(/[.*+?^${}()|[\]\\]/g, "\\$&");
  }

  function applyPreviewColors(data) {
    var currentColors = data && data.current;
    var newColors = data && data.new;
    if (!currentColors || !newColors) return;

    var replacements = [];
    for (var k = 0; k < COLOR_KEYS.length; k++) {
      var key = COLOR_KEYS[k];
      var orig = normalizeHex(currentColors[key]);
      var target = normalizeHex(newColors[key]);
      if (orig && target && orig !== target) {
        replacements.push({ orig: orig, target: target, key: key });
      }
    }
    if (replacements.length === 0) return;

    var styleTags = document.querySelectorAll("style");
    for (var s = 0; s < styleTags.length; s++) {
      var text = styleTags[s].textContent;
      var mod = false;
      for (var r = 0; r < replacements.length; r++) {
        var re = new RegExp(escapeRegex(replacements[r].orig), "gi");
        var mm = text.match(re);
        if (mm) {
          text = text.replace(re, replacements[r].target);
          mod = true;
        }
      }
      if (mod) styleTags[s].textContent = text;
    }

    var nodes = document.body.querySelectorAll("*");
    for (var i = 0; i < nodes.length; i++) {
      var el = nodes[i];
      var cs = window.getComputedStyle(el);
      var bgHex = normalizeHex(cs.backgroundColor);
      var fgHex = normalizeHex(cs.color);
      for (var ri = 0; ri < replacements.length; ri++) {
        if (bgHex === replacements[ri].orig) {
          el.style.backgroundColor = replacements[ri].target;
          break;
        }
      }
      for (var rf = 0; rf < replacements.length; rf++) {
        if (fgHex === replacements[rf].orig) {
          el.style.color = replacements[rf].target;
          break;
        }
      }
    }
  }

  function getTargetClass(elementType) {
    var classMap = {
      heading: "hero-heading-class",
      description: "hero-description-class",
      "about-us-story-header": "about-us-story-header-class",
      "about-us-story-content": "about-us-story-content-class",
    };
    return classMap[elementType] || null;
  }

  function updateHeroContent(data) {
    if (!data || !data.elementType || !data.content) {
      console.warn("LeadConnector: Invalid updateContent data", data);
      return;
    }
    var targetClass = getTargetClass(data.elementType);
    if (!targetClass) {
      console.warn("LeadConnector: Unknown elementType", data.elementType);
      return;
    }

    var elements = document.querySelectorAll("." + targetClass);
    if (elements.length === 0) {
      console.warn("LeadConnector: No elements found with class", targetClass);
      return;
    }

    elements.forEach(function (element) {
      var contentElement =
        element.querySelector(
          "h1, h2, h3, h4, h5, h6, .elementor-heading-title",
        ) ||
        element.querySelector(
          ".elementor-widget-container, .elementor-text-editor",
        ) ||
        element;
      if (data.content) contentElement.textContent = data.content;
    });

    try {
      if (window.parent && window.parent !== window) {
        window.parent.postMessage(
          {
            source: "leadconnector-elementor-iframe",
            type: "content-updated",
            elementType: data.elementType,
            success: true,
          },
          "*",
        );
      }
    } catch (e) {
      console.warn("LeadConnector: Could not send confirmation to parent", e);
    }
  }

  function handleParentMessage(event) {
    if (!event.data || event.data.source !== "leadconnector-parent-app") return;
    var messageType = event.data.type;
    var messageData = event.data.data;

    if (messageType === "toggleHighlight") {
      highlightEnabled = !!(messageData && messageData.enabled);
      var styleTag = document.getElementById("leadconnector-elementor-highlight-css");
      if (styleTag) styleTag.disabled = !highlightEnabled;
      var overlays = document.querySelectorAll(".leadconnector-regenerate-image-overlay");
      for (var oi = 0; oi < overlays.length; oi++) {
        overlays[oi].style.display = highlightEnabled ? "" : "none";
      }
    }
    if (messageType === "updateContent") updateHeroContent(messageData);
    if (messageType === "updateColors") applyPreviewColors(messageData);
  }

  window.addEventListener("message", handleParentMessage);
})();

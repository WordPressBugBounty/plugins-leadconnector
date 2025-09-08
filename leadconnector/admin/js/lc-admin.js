(function () {
  "use strict";

  /**
   * All of the code for your admin-facing JavaScript source
   * should reside in this file.
   *
   * Note: It has been assumed you will write jQuery code here, so the
   * $ function reference has been prepared for usage within the scope
   * of this function.
   *
   * This enables you to define handlers, for when the DOM is ready:
   *
   * $(function() {
   *
   * });
   *
   * When the window is loaded:
   *
   * $( window ).load(function() {
   *
   * });
   *
   * ...and/or other possibilities.
   *
   * Ideally, it is not considered best practise to attach more than a
   * single DOM-ready or window-load handler for a particular page.
   * Although scripts in the WordPress core, Plugins and Themes may be
   * practising this, we should strive to set a better example in our own work.
   */

  function toggleInputs(enabled) {
    const element = document.getElementById("lc_text-widget--submit");
    if (!!element) {
      if (enabled) {
        element.value = "Pull and Save";
      } else {
        element.value = "Save";
      }
    }
  }

  // Function to make API call for purging all domains
  function makeApiCall(siteId, siteToken, nonce) {
    // Single Rocket.net CDN API endpoint to purge everything (including additional domains)
    const purgeUrl = `https://api.rocket.net/v1/sites/${siteId}/cache/purge_everything?include_additional_domains=true`;

    // Show loading state
    const button = document.querySelector('#wp-admin-bar-cdn_menu_purge_all_domains a');
    if (button) {
      const originalText = button.textContent;
      button.textContent = 'Purging...';
      button.style.opacity = '0.7';
      button.style.pointerEvents = 'none';

      fetch(purgeUrl, {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${siteToken}`,
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        },
        // Some Rocket.net endpoints require a body, even when not used
        body: JSON.stringify({})
      })
        .then(response => {
          if (response.ok) {
            return response.json();
          } else {
            throw new Error(`API request failed with status ${response.status}`);
          }
        })
        .catch(error => {
          alert(`Failed to purge cache: ${error.message}`);
        })
        .finally(() => {
          // Restore button state
          if (button) {
            button.textContent = originalText;
            button.style.opacity = '1';
            button.style.pointerEvents = 'auto';
          }
        });
    }
  }

  // Function to add "Purge everything on all domains" button to CDN menu
  function addPurgeAllDomainsButton() {
    const cdnMenuList = document.getElementById("wp-admin-bar-cdn_menu-default");

    if (cdnMenuList && !cdnMenuList.querySelector("#wp-admin-bar-cdn_menu_purge_all_domains")) {
      // Create the list item
      const listItem = document.createElement("li");
      listItem.id = "wp-admin-bar-cdn_menu_purge_all_domains";
      listItem.setAttribute("role", "group");

      // Create the anchor element
      const anchor = document.createElement("a");
      anchor.className = "ab-item";
      anchor.setAttribute("role", "menuitem");
      anchor.setAttribute("href", "#");
      anchor.textContent = "Purge everything on all domains";

      // Add click event handler
      anchor.addEventListener("click", function (e) {
        e.preventDefault();

        // Make direct API call without confirmation
        if (typeof cdnConfig !== 'undefined' && cdnConfig.siteId && cdnConfig.siteToken) {
          makeApiCall(cdnConfig.siteId, cdnConfig.siteToken, cdnConfig.nonce);
        } else {
          alert("CDN configuration is missing. Please check wp-config.php for CDN_SITE_ID and CDN_SITE_TOKEN.");
        }
      });

      // Append anchor to list item
      listItem.appendChild(anchor);

      // Add the list item to the CDN menu
      cdnMenuList.appendChild(listItem);
    }
  }

  // Observer to watch for CDN menu appearance
  function observeCDNMenu() {
    // First, try to add the button immediately in case the menu is already there
    addPurgeAllDomainsButton();

    // Create a MutationObserver to watch for DOM changes
    const observer = new MutationObserver(function (mutations) {
      mutations.forEach(function (mutation) {
        // Check if any new nodes were added
        if (mutation.type === 'childList' && mutation.addedNodes.length > 0) {
          // Try to add the button when new nodes are added
          addPurgeAllDomainsButton();
        }
      });
    });

    // Start observing the document body for changes
    observer.observe(document.body, {
      childList: true,
      subtree: true
    });

  }



  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function () {
      observeCDNMenu();
    });
  } else {
    // DOM is already loaded
    observeCDNMenu();
  }

  window.addEventListener("load", function () {
    var enabledTextWidgetCheckBox = document.querySelector(
      "#lead_connector_setting_enable_text_widget"
    );
    if (!!enabledTextWidgetCheckBox) {
      toggleInputs(enabledTextWidgetCheckBox.checked ? true : false);

      enabledTextWidgetCheckBox.addEventListener(
        "change",
        function () {
          toggleInputs(enabledTextWidgetCheckBox.checked ? true : false);
        },
        false
      );
    }
  });
})();

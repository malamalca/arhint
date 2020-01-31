(function($) {
  'use strict';

  let _defaults = {
    source: null, // Source url
    limit: Infinity, // Limit of results the autocomplete shows
    onSelect: null, // Callback for when autocompleted
    onSearch: null, // Callback for when search initiated
    onRenderItem: null,
    onOpenStart: null,
    onOpenEnd: null,
    minLength: 1, // Min characters before autocomplete starts
  };

  /**
   * @class
   *
   */
  class AutocompleteAjax extends Component {
    /**
     * Construct AutocompleteAjax instance
     * @constructor
     * @param {Element} el
     * @param {Object} options
     */
    constructor(el, options) {
      super(AutocompleteAjax, el, options);

      this.el.M_AutocompleteAjax = this;

      /**
       * Options for the autocomplete
       * @member AutocompleteAjax#options
       * @prop {Number} duration
       * @prop {Number} dist
       * @prop {number} shift
       * @prop {number} padding
       * @prop {Boolean} fullWidth
       * @prop {Boolean} indicators
       * @prop {Boolean} noWrap
       * @prop {Function} onCycleTo
       */
      this.options = $.extend({}, AutocompleteAjax.defaults, options);

      // Setup
      this.isOpen = false;
      this.count = 0;
      this.activeIndex = -1;
      this.oldVal;
      this.$inputField = this.$el.closest('.input-field');
      this.$active = $();
      this._mousedown = false;
      this._setupDropdown();

      this._setupEventHandlers();
    }

    static get defaults() {
      return _defaults;
    }

    static init(els, options) {
      return super.init(this, els, options);
    }

    /**
     * Get Instance
     */
    static getInstance(el) {
      let domElem = !!el.jquery ? el[0] : el;
      return domElem.M_AutocompleteAjax;
    }

    /**
     * Teardown component
     */
    destroy() {
      this._removeEventHandlers();
      this._removeDropdown();
      this.el.M_AutocompleteAjax = undefined;
    }

    /**
     * Setup Event Handlers
     */
    _setupEventHandlers() {
      this._handleInputBlurBound = this._handleInputBlur.bind(this);
      this._handleInputKeyupAndFocusBound = this._handleInputKeyupAndFocus.bind(this);
      this._handleInputKeydownBound = this._handleInputKeydown.bind(this);
      this._handleInputClickBound = this._handleInputClick.bind(this);
      this._handleContainerMousedownAndTouchstartBound = this._handleContainerMousedownAndTouchstart.bind(
        this
      );
      this._handleContainerMouseupAndTouchendBound = this._handleContainerMouseupAndTouchend.bind(
        this
      );

      this.el.addEventListener('blur', this._handleInputBlurBound);
      this.el.addEventListener('keyup', this._handleInputKeyupAndFocusBound);
      this.el.addEventListener('focus', this._handleInputKeyupAndFocusBound);
      this.el.addEventListener('keydown', this._handleInputKeydownBound);
      this.el.addEventListener('click', this._handleInputClickBound);
      this.container.addEventListener(
        'mousedown',
        this._handleContainerMousedownAndTouchstartBound
      );
      this.container.addEventListener('mouseup', this._handleContainerMouseupAndTouchendBound);

      if (typeof window.ontouchstart !== 'undefined') {
        this.container.addEventListener(
          'touchstart',
          this._handleContainerMousedownAndTouchstartBound
        );
        this.container.addEventListener('touchend', this._handleContainerMouseupAndTouchendBound);
      }
    }

    /**
     * Remove Event Handlers
     */
    _removeEventHandlers() {
      this.el.removeEventListener('blur', this._handleInputBlurBound);
      this.el.removeEventListener('keyup', this._handleInputKeyupAndFocusBound);
      this.el.removeEventListener('focus', this._handleInputKeyupAndFocusBound);
      this.el.removeEventListener('keydown', this._handleInputKeydownBound);
      this.el.removeEventListener('click', this._handleInputClickBound);
      this.container.removeEventListener(
        'mousedown',
        this._handleContainerMousedownAndTouchstartBound
      );
      this.container.removeEventListener('mouseup', this._handleContainerMouseupAndTouchendBound);

      if (typeof window.ontouchstart !== 'undefined') {
        this.container.removeEventListener(
          'touchstart',
          this._handleContainerMousedownAndTouchstartBound
        );
        this.container.removeEventListener(
          'touchend',
          this._handleContainerMouseupAndTouchendBound
        );
      }
    }

    /**
     * Setup dropdown
     */
    _setupDropdown() {
      this.container = document.createElement('ul');
      this.container.id = `autocomplete-options-${M.guid()}`;
      $(this.container).addClass('autocomplete-content dropdown-content');
      this.$inputField.append(this.container);
      this.el.setAttribute('data-target', this.container.id);

      this.dropdown = M.Dropdown.init(this.el, {
        autoFocus: false,
        closeOnClick: false,
        coverTrigger: false,
        onItemClick: (itemEl) => {
          this.selectOption($(itemEl));
        }
      });

      // Sketchy removal of dropdown click handler
      this.el.removeEventListener('click', this.dropdown._handleClickBound);
    }

    /**
     * Remove dropdown
     */
    _removeDropdown() {
      this.container.parentNode.removeChild(this.container);
    }

    /**
     * Handle Input Blur
     */
    _handleInputBlur() {
      if (!this._mousedown) {
        this.close();
        this._resetAutocomplete();
      }
    }

    /**
     * Handle Input Keyup and Focus
     * @param {Event} e
     */
    _handleInputKeyupAndFocus(e) {
      if (e.type === 'keyup') {
        AutocompleteAjax._keydown = false;
      }

      this.count = 0;
      let val = this.el.value.toLowerCase();

      // Don't capture enter or arrow key usage.
      if (e.keyCode === 13 || e.keyCode === 38 || e.keyCode === 40) {
        return;
      }

      // Check if the input isn't empty
      // Check if focus triggered by tab
      if (this.oldVal !== val && (M.tabPressed || e.type !== 'focus')) {
        this.open();
      }

      // Update oldVal
      this.oldVal = val;
    }

    /**
     * Handle Input Keydown
     * @param {Event} e
     */
    _handleInputKeydown(e) {
      AutocompleteAjax._keydown = true;

      // Arrow keys and enter key usage
      let keyCode = e.keyCode,
        liElement,
        numItems = $(this.container).children('li').length;

      // select element on Enter
      if (keyCode === M.keys.ENTER && this.activeIndex >= 0) {
        liElement = $(this.container)
          .children('li')
          .eq(this.activeIndex);
        if (liElement.length) {
          this.selectOption(liElement);
          e.preventDefault();
        }
        return;
      }

      // Capture up and down key
      if (keyCode === M.keys.ARROW_UP || keyCode === M.keys.ARROW_DOWN) {
        e.preventDefault();

        if (keyCode === M.keys.ARROW_UP && this.activeIndex > 0) {
          this.activeIndex--;
        }

        if (keyCode === M.keys.ARROW_DOWN && this.activeIndex < numItems - 1) {
          this.activeIndex++;
        }

        this.$active.removeClass('active');
        if (this.activeIndex >= 0) {
          this.$active = $(this.container)
            .children('li')
            .eq(this.activeIndex);
          this.$active.addClass('active');
        }
      }
    }

    /**
     * Handle Input Click
     * @param {Event} e
     */
    _handleInputClick(e) {
      let val = this.el.value.toLowerCase();
      if (this.oldVal !== val) {
        this.open();
      }
    }

    /**
     * Handle Container Mousedown and Touchstart
     * @param {Event} e
     */
    _handleContainerMousedownAndTouchstart(e) {
      this._mousedown = true;
    }

    /**
     * Handle Container Mouseup and Touchend
     * @param {Event} e
     */
    _handleContainerMouseupAndTouchend(e) {
      this._mousedown = false;
    }

    /**
     * Highlight partial match
     */
    _highlight(string, $el) {
      let img = $el.find('img');
      let matchStart = $el
          .text()
          .toLowerCase()
          .indexOf('' + string.toLowerCase() + ''),
        matchEnd = matchStart + string.length - 1,
        beforeMatch = $el.text().slice(0, matchStart),
        matchText = $el.text().slice(matchStart, matchEnd + 1),
        afterMatch = $el.text().slice(matchEnd + 1);

      if (matchStart >= 0) {
        $el.html(
            `<span>${beforeMatch}<span class='highlight'>${matchText}</span>${afterMatch}</span>`
        );
        if (img.length) {
            $el.prepend(img);
        }
      }
    }

    /**
     * Reset current element position
     */
    _resetCurrentElement() {
      this.activeIndex = -1;
      this.$active.removeClass('active');
    }

    /**
     * Reset autocomplete elements
     */
    _resetAutocomplete() {
      $(this.container).empty();
      this._resetCurrentElement();
      // MN: do not reset oldval
      //this.oldVal = null;
      this.isOpen = false;
      this._mousedown = false;
    }

    /**
     * Select autocomplete option
     * @param {Element} el  Autocomplete option list item element
     */
    selectOption(el) {
      //let text = el.text().trim();
      let item = el.data('item');

      let text = item.value;
      this.el.value = text;
      this.$el.trigger('change');
      this._resetAutocomplete();
      this.close();

      this.oldVal = text.toLowerCase();

      // Handle onSelect callback.
      if (typeof this.options.onSelect === 'function') {
        this.options.onSelect.call(this.$el, item);
      }
    }

    /**
     * Render dropdown content
     * @param {Object} data  data set
     * @param {String} val  current input value
     */
    _renderDropdown(data, val) {
      this._resetAutocomplete();

      // Render
      for (let i = 0; i < data.length; i++) {
        let entry = data[i];

        let $autocompleteOption = $('<li></li>');
        $autocompleteOption.append('<span>' + entry.label + '</span>');

        if (typeof this.options.onRenderItem === 'function') {
            $autocompleteOption = $(this.options.onRenderItem.call(this.$el, $autocompleteOption, entry));
        }

        $autocompleteOption.data('item', entry);

        $(this.container).append($autocompleteOption);
        //this._highlight(val, $autocompleteOption);

        this.count++;

        // Break if past limit
        if (this.count >= this.options.limit) {
          break;
        }
      }
    }

    _isJson(jsonData) {
        if (typeof jsonData === "object") {
            return jsonData;
        }

        try {
            var o = JSON.parse(jsonData);

            // Handle non-exception-throwing cases:
            // Neither JSON.parse(false) or JSON.parse(1234) throw errors, hence the type-checking,
            // but... JSON.parse(null) returns null, and typeof null === "object",
            // so we must check for that, too. Thankfully, null is falsey, so this suffices:
            if (o && typeof o === "object") {
                return o;
            }
        }
        catch (e) {  }

        return false;
    }

    /**
     * Open Autocomplete Dropdown
     */
    open() {
      let val = this.el.value.toLowerCase();

      this._resetAutocomplete();

      if (val.length >= this.options.minLength) {
        var $this = this;

        // do a ajax request
        let requestUrl = this.options.source;
        if (requestUrl.indexOf('?') >= 0) {
            requestUrl = requestUrl + '&';
        } else {
            requestUrl = requestUrl + '?';
        }
        requestUrl = requestUrl + 'term=' + encodeURIComponent(val);

        jQuery.get(requestUrl, function(response) {
            $this.isOpen = true;

            let json = $this._isJson(response);
            if (json) {
                $this._renderDropdown(json, val);

                // Open dropdown
                if (!$this.dropdown.isOpen) {
                    $this.dropdown.open();
                } else {
                    // Recalculate dropdown when its already open
                    $this.dropdown.recalculateDimensions();
                }

                if (typeof $this.options.onOpenEnd === 'function') {
                    $this.options.onOpenEnd.call($this, $this.container);
                }

            }
        });

        // Handle onSearch callback.
        if (typeof this.options.onSearch === 'function') {
            this.options.onSearch.call(this.$el);
        }
      }
    }

    /**
     * Close Autocomplete Dropdown
     */
    close() {
      this.dropdown.close();
    }
  }


  /**
   * @static
   * @memberof AutocompleteAjax
   */
  AutocompleteAjax._keydown = false;

  M.AutocompleteAjax = AutocompleteAjax;

  if (M.jQueryLoaded) {
    M.initializeJqueryWrapper(AutocompleteAjax, 'autocompleteajax', 'M_AutocompleteAjax');
  }
})(cash);

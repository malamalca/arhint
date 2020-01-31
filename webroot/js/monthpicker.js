(function($) {
  'use strict';

  let _defaults = {
    // Close when Month is selected
    autoClose: true,

    // the default output format for the input field value
    format: 'yyyy-dd',

    // Used to create month string from current input string
    parse: null,

    // The initial month to view when first opened
    defaultMonth: null,

    // Make the `defaultMonth` the initial selected value
    setDefaultMonth: false,

    // The earliest month that can be selected
    minMonth: null,
    // Thelatest month that can be selected
    maxMonth: null,

    // Number of years either side, or array of upper/lower range
    yearRange: 10,

    // used internally (don't config outside)
    minYear: 1900,
    maxYear: 2050,

    startRange: null,
    endRange: null,

    isRTL: false,

    // Specify a DOM element to render the calendar in
    container: null,

    // Show clear button
    showClearBtn: false,

    // internationalization
    i18n: {
      cancel: 'Cancel',
      clear: 'Clear',
      done: 'Ok',
      previousYear: '‹',
      nextYear: '›',
      months: [
        'January',
        'February',
        'March',
        'April',
        'May',
        'June',
        'July',
        'August',
        'September',
        'October',
        'November',
        'December'
      ],
      monthsShort: [
        'Jan',
        'Feb',
        'Mar',
        'Apr',
        'May',
        'Jun',
        'Jul',
        'Aug',
        'Sep',
        'Oct',
        'Nov',
        'Dec'
      ]
    },

    // events array
    events: [],

    // callback function
    onSelect: null,
    onOpen: null,
    onClose: null,
    onDraw: null
  };

  /**
   * @class
   *
   */
  class Monthpicker extends Component {
    /**
     * Construct Monthpicker instance and set up overlay
     * @constructor
     * @param {Element} el
     * @param {Object} options
     */
    constructor(el, options) {
      super(Monthpicker, el, options);

      this.el.M_Monthpicker = this;

      this.options = $.extend({}, Monthpicker.defaults, options);

      // make sure i18n defaults are not lost when only few i18n option properties are passed
      if (!!options && options.hasOwnProperty('i18n') && typeof options.i18n === 'object') {
        this.options.i18n = $.extend({}, Monthpicker.defaults.i18n, options.i18n);
      }

      this.id = M.guid();

      this._setupVariables();
      this._insertHTMLIntoDOM();
      this._setupModal();

      this._setupEventHandlers();

      if (!this.options.defaultMonth) {
        this.options.defaultMonth = this.el.value;
      }

      let defMonth = this.options.defaultMonth;
      if (Monthpicker._isMonth(defMonth)) {
        if (this.options.setDefaultMonth) {
          this.setMonth(defMonth, true);
          this.setInputValue();
        } else {
          this.gotoMonth(defMonth);
        }
      } else {
        var today = new Date();
        var todaysMonth = today.getMonth();
        if (todaysMonth < 10) {
            todaysMonth = '0' + todaysMonth;
        }
        var month_year = today.getFullYear() + '-' + todaysMonth;
        this.gotoMonth(month_year);
      }

      /**
       * Describes open/close state of monthpicker
       * @type {Boolean}
       */
      this.isOpen = false;
    }

    static get defaults() {
      return _defaults;
    }

    static init(els, options) {
      return super.init(this, els, options);
    }

    static _isMonth(obj) {
      // todo
      return typeof obj != 'undefined' && obj != null && obj.length == 7;
      //return /Date/.test(Object.prototype.toString.call(obj)) && !isNaN(obj.getTime());
    }

    /**
     * Get Instance
     */
    static getInstance(el) {
      let domElem = !!el.jquery ? el[0] : el;
      return domElem.M_Monthpicker;
    }

    /**
     * Teardown component
     */
    destroy() {
      this._removeEventHandlers();
      this.modal.destroy();
      $(this.modalEl).remove();
      this.destroySelects();
      this.el.M_Monthpicker = undefined;
    }

    destroySelects() {
      let oldYearSelect = this.calendarEl.querySelector('.orig-select-year');
      if (oldYearSelect) {
        M.FormSelect.getInstance(oldYearSelect).destroy();
      }
    }

    _insertHTMLIntoDOM() {
      if (this.options.showClearBtn) {
        $(this.clearBtn).css({ visibility: '' });
        this.clearBtn.innerHTML = this.options.i18n.clear;
      }

      this.doneBtn.innerHTML = this.options.i18n.done;
      this.cancelBtn.innerHTML = this.options.i18n.cancel;

      if (this.options.container) {
        this.$modalEl.appendTo(this.options.container);
      } else {
        this.$modalEl.insertBefore(this.el);
      }
    }

    _setupModal() {
      this.modalEl.id = 'modal-' + this.id;
      this.modal = M.Modal.init(this.modalEl, {
        onCloseEnd: () => {
          this.isOpen = false;
        }
      });
    }

    toString(format) {
      format = format || this.options.format;
      if (!Monthpicker._isMonth(this.month_year)) {
        return '';
      }

      let formatArray = format.split(/(m{1,4}|y{4}|yy|!.)/g);
      let formattedMonth = formatArray
        .map((label) => {
          if (this.formats[label]) {
            return this.formats[label]();
          }

          return label;
        })
        .join('');

      return formattedMonth;
      //return this.month_year;
    }

    setMonth(month, preventOnSelect) {
      if (!month) {
        this.month_year = null;
        this._renderYearDisplay();
        return this.draw();
      }

      if (!Monthpicker._isMonth(month)) {
        return;
      }

      let min = this.options.minMonth,
        max = this.options.maxMonth;

      if (Monthpicker._isMonth(min) && month < min) {
        month = min;
      } else if (Monthpicker._isMonth(max) && month > max) {
        month = max;
      }

      this.month_year = month;

      this._renderYearDisplay();

      this.gotoMonth(this.month_year);

      if (!preventOnSelect && typeof this.options.onSelect === 'function') {
        this.options.onSelect.call(this, this.month_year);
      }
    }

    setInputValue() {
      this.el.value = this.toString();
      this.$el.trigger('change', { firedBy: this });
    }

    _renderYearDisplay() {
      //this.yearTextEl.innerHTML = this.month_year
    }

    _extractYear(month_year) {
        return month_year.substr(0, 4);
    }

    _extractMonth(month_year) {
        return month_year.substr(5, 2);
    }

    /**
     * change view to a specific month
     */
    gotoMonth(month_year) {
      if (!Monthpicker._isMonth(month_year)) {
        return;
      }

      this.month_year = month_year;

      this.draw();
    }

    nextYear() {
      let nextYear = parseInt(this._extractYear(this.month_year)) + 1;
      if (nextYear <= this.options.maxYear) {
        this.gotoMonth(nextYear + '-' + this._extractMonth(this.month_year));
      }
    }

    prevYear() {
      let prevYear = parseInt(this._extractYear(this.month_year)) - 1;
      if (prevYear >= this.options.minYear) {
        this.gotoMonth(prevYear + '-' + this._extractMonth(this.month_year));
      }
    }

    render(year, month, randId) {
      let opts = this.options,
        currentMonth = (new Date()).getMonth() + 1,
        currentMonthYear = (new Date()).getFullYear() + '-' + (currentMonth < 10 ? '0' + currentMonth : currentMonth),
        data = [],
        row = [];

      let cells = 12;

      for (let i = 1, r = 0; i <= cells; i++) {

        let aMonth = '' + year + '-' + (i < 10 ? '0' + i : i),
          isSelected = Monthpicker._isMonth(this.month_year)
            ? aMonth == this.month_year
            : false,
          isToday = aMonth == currentMonthYear,
          hasEvent = opts.events.indexOf(month) !== -1 ? true : false,
          isEmpty = false,
          monthNumber = i,
          yearNumber = year,
          isStartRange = opts.startRange && (opts.startRange == aMonth),
          isEndRange = opts.endRange && (opts.endRange == aMonth),
          isInRange =
            opts.startRange && opts.endRange && opts.startRange < aMonth && aMonth < opts.endRange,
          isDisabled =
            (opts.minMonth && day < opts.minMonth) ||
            (opts.maxMonth && day > opts.maxMonth);

        let monthConfig = {
          month: monthNumber < 10 ? '0' + monthNumber : monthNumber,
          year: yearNumber,
          hasEvent: hasEvent,
          isSelected: isSelected,
          isToday: isToday,
          isDisabled: isDisabled,
          isEmpty: isEmpty,
          isStartRange: isStartRange,
          isEndRange: isEndRange,
          isInRange: isInRange,
          caption: this.renderMonthName(this.options, monthNumber - 1, false)
        };

        row.push(this.renderMonth(monthConfig));

        if (++r === 3) {
          data.push(this.renderRow(row, opts.isRTL, false));
          row = [];
          r = 0;
        }
      }
      return this.renderTable(opts, data, randId);
    }

    renderMonth(opts) {
      let arr = [];
      let ariaSelected = 'false';
      if (opts.isDisabled) {
        arr.push('is-disabled');
      }
      if (opts.isToday) {
        arr.push('is-today');
      }
      if (opts.isSelected) {
        arr.push('is-selected');
        ariaSelected = 'true';
      }
      if (opts.hasEvent) {
        arr.push('has-event');
      }
      if (opts.isInRange) {
        arr.push('is-inrange');
      }
      if (opts.isStartRange) {
        arr.push('is-startrange');
      }
      if (opts.isEndRange) {
        arr.push('is-endrange');
      }
      return (
        `<td data-month="${opts.month}" class="${arr.join(' ')}" aria-selected="${ariaSelected}">` +
        `<button class="monthpicker-month-button" type="button" data-month="${opts.month}" data-year="${opts.year}">${opts.caption}</button>` +
        '</td>'
      );
    }

    renderRow(months, isRTL, isRowSelected) {
      return (
        '<tr class="monthpicker-row' +
        (isRowSelected ? ' is-selected' : '') +
        '">' +
        (isRTL ? months.reverse() : months).join('') +
        '</tr>'
      );
    }

    renderTable(opts, data, randId) {
      return (
        '<div class="monthpicker-table-wrapper"><table cellpadding="0" cellspacing="0" class="monthpicker-table" role="grid" aria-labelledby="' +
        randId +
        '">' +
        this.renderHead(opts) +
        this.renderBody(data) +
        '</table></div>'
      );
    }

    renderHead(opts) {
      return '';
    }

    renderBody(rows) {
      return '<tbody>' + rows.join('') + '</tbody>';
    }

    renderTitle(instance, c, year, month, refYear, randId) {
      let i,
        j,
        arr,
        opts = this.options,
        isMinYear = year === opts.minYear,
        isMaxYear = year === opts.maxYear,
        html =
          '<div id="' +
          randId +
          '" class="monthpicker-controls" role="heading" aria-live="assertive">',
        monthHtml,
        yearHtml,
        prev = true,
        next = true;

      if ($.isArray(opts.yearRange)) {
        i = opts.yearRange[0];
        j = opts.yearRange[1] + 1;
      } else {
        i = year - opts.yearRange;
        j = 1 + year + opts.yearRange;
      }

      for (arr = []; i < j && i <= opts.maxYear; i++) {
        if (i >= opts.minYear) {
          arr.push(`<option value="${i}" ${i == year ? 'selected="selected"' : ''}>${i}</option>`);
        }
      }

      yearHtml = `<select class="monthpicker-select orig-select-year" tabindex="-1">${arr.join(
        ''
      )}</select>`;

      let leftArrow =
        '<svg fill="#000000" height="24" viewBox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg"><path d="M15.41 16.09l-4.58-4.59 4.58-4.59L14 5.5l-6 6 6 6z"/><path d="M0-.5h24v24H0z" fill="none"/></svg>';
      html += `<button class="year-prev${
        prev ? '' : ' is-disabled'
      }" type="button">${leftArrow}</button>`;

      html += '<div class="selects-container">' + yearHtml + '</div>';

      if (isMinYear) {
        prev = false;
      }

      if (isMaxYear) {
        next = false;
      }

      let rightArrow =
        '<svg fill="#000000" height="24" viewBox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg"><path d="M8.59 16.34l4.58-4.59-4.58-4.59L10 5.75l6 6-6 6z"/><path d="M0-.25h24v24H0z" fill="none"/></svg>';
      html += `<button class="year-next${
        next ? '' : ' is-disabled'
      }" type="button">${rightArrow}</button>`;

      return (html += '</div>');
    }

    /**
     * refresh the HTML
     */
    draw(force) {
      if (!this.isOpen && !force) {
        return;
      }
      let opts = this.options,
        minYear = opts.minYear,
        maxYear = opts.maxYear,
        html = '',
        randId;

      if (this._y <= minYear) {
        this._y = minYear;
      }
      if (this._y >= maxYear) {
        this._y = maxYear;
      }

      randId =
        'monthpicker-title-' +
        Math.random()
          .toString(36)
          .replace(/[^a-z]+/g, '')
          .substr(0, 2);

      for (let c = 0; c < 1; c++) {
        this._renderYearDisplay();
        html +=
          this.renderTitle(
            this,
            c,
            this._extractYear(this.month_year),
            this._extractMonth(this.month_year),
            this._extractYear(this.month_year),
            randId
          ) + this.render(this._extractYear(this.month_year), this._extractMonth(this.month_year), randId);
      }

      this.destroySelects();

      this.calendarEl.innerHTML = html;

      // Init Materialize Select
      let yearSelect = this.calendarEl.querySelector('.orig-select-year');
      M.FormSelect.init(yearSelect, {
        classes: 'select-year',
        dropdownOptions: { container: document.body, constrainWidth: false }
      });

      // Add change handlers for select
      yearSelect.addEventListener('change', this._handleYearChange.bind(this));

      if (typeof this.options.onDraw === 'function') {
        this.options.onDraw(this);
      }
    }

    /**
     * Setup Event Handlers
     */
    _setupEventHandlers() {
      this._handleInputKeydownBound = this._handleInputKeydown.bind(this);
      this._handleInputClickBound = this._handleInputClick.bind(this);
      this._handleInputChangeBound = this._handleInputChange.bind(this);
      this._handleCalendarClickBound = this._handleCalendarClick.bind(this);
      this._finishSelectionBound = this._finishSelection.bind(this);
      this._handleYearChange = this._handleYearChange.bind(this);
      this._closeBound = this.close.bind(this);

      this.el.addEventListener('click', this._handleInputClickBound);
      this.el.addEventListener('keydown', this._handleInputKeydownBound);
      this.el.addEventListener('change', this._handleInputChangeBound);
      this.calendarEl.addEventListener('click', this._handleCalendarClickBound);
      this.doneBtn.addEventListener('click', this._finishSelectionBound);
      this.cancelBtn.addEventListener('click', this._closeBound);

      if (this.options.showClearBtn) {
        this._handleClearClickBound = this._handleClearClick.bind(this);
        this.clearBtn.addEventListener('click', this._handleClearClickBound);
      }
    }

    _setupVariables() {
      this.$modalEl = $(Monthpicker._template);
      this.modalEl = this.$modalEl[0];

      this.calendarEl = this.modalEl.querySelector('.monthpicker-calendar');

      if (this.options.showClearBtn) {
        this.clearBtn = this.modalEl.querySelector('.monthpicker-clear');
      }
      this.doneBtn = this.modalEl.querySelector('.monthpicker-done');
      this.cancelBtn = this.modalEl.querySelector('.monthpicker-cancel');

      this.formats = {
        m: () => {
          return parseInt(this.month_year.substr(5,2));
        },
        mm: () => {
          let m = parseInt(this.month_year.substr(5,2));
          return (m < 10 ? '0' : '') + m;
        },
        mmm: () => {
          return this.options.i18n.monthsShort[parseInt(this.month_year.substr(5, 2)) - 1];
        },
        mmmm: () => {
          return this.options.i18n.months[parseInt(this.month_year.substr(5, 2)) - 1];
        },
        yy: () => {
          return this.month_year.substr(2, 2);
        },
        yyyy: () => {
          return this.month_year.substr(0, 4);
        }
      };
    }

    /**
     * Remove Event Handlers
     */
    _removeEventHandlers() {
      this.el.removeEventListener('click', this._handleInputClickBound);
      this.el.removeEventListener('keydown', this._handleInputKeydownBound);
      this.el.removeEventListener('change', this._handleInputChangeBound);
      this.calendarEl.removeEventListener('click', this._handleCalendarClickBound);
    }

    _handleInputClick() {
      this.open();
    }

    _handleInputKeydown(e) {
      if (e.which === M.keys.ENTER) {
        e.preventDefault();
        this.open();
      }
    }

    _handleCalendarClick(e) {
      if (!this.isOpen) {
        return;
      }

      let $target = $(e.target);
      if (!$target.hasClass('is-disabled')) {
        if (
          $target.hasClass('monthpicker-month-button') &&
          !$target.hasClass('is-empty') &&
          !$target.parent().hasClass('is-disabled')
        ) {
          this.setMonth(
              e.target.getAttribute('data-year') + '-' +
              e.target.getAttribute('data-month')
          );
          if (this.options.autoClose) {
            this._finishSelection();
          }
        } else if ($target.closest('.year-prev').length) {
          this.prevYear();
        } else if ($target.closest('.year-next').length) {
          this.nextYear();
        }
      }
    }

    _handleClearClick() {
      this.month_year = null;
      this.setInputValue();
      this.close();
    }

    _handleYearChange(e) {
      this.gotoYear(e.target.value);
    }

    /**
     * change view to a specific full year (e.g. "2012")
     */
    gotoYear(year) {
      if (!isNaN(year)) {
        year = parseInt(year, 10);

        this.gotoMonth(year + '-' + this._extractMonth(this.month_year));
      }
    }

    _handleInputChange(e) {
      let month_year;

      // Prevent change event from being fired when triggered by the plugin
      if (e.firedBy === this) {
        return;
      }
      if (this.options.parse) {
        month_year = this.options.parse(this.el.value, this.options.format);
      } else {
        month_year = this.el.value;
      }

      if (Monthpicker._isMonth(month_year)) {
        this.setMonth(month_year);
      }
    }

    renderMonthName(opts, month, abbr) {

      return abbr ? opts.i18n.monthsAbbrev[month] : opts.i18n.months[month];
    }

    /**
     * Set input value to the selected month and close Monthpicker
     */
    _finishSelection() {
      this.setInputValue();
      this.close();
    }

    /**
     * Open Monthpicker
     */
    open() {
      if (this.isOpen) {
        return;
      }

      this.isOpen = true;
      if (typeof this.options.onOpen === 'function') {
        this.options.onOpen.call(this);
      }
      this.draw();
      this.modal.open();
      return this;
    }

    /**
     * Close Monthpicker
     */
    close() {
      if (!this.isOpen) {
        return;
      }

      this.isOpen = false;
      if (typeof this.options.onClose === 'function') {
        this.options.onClose.call(this);
      }
      this.modal.close();
      return this;
    }
  }

  Monthpicker._template = [
    '<div class= "modal monthpicker-modal">',
    '<div class="modal-content monthpicker-container">',
    '<div class="monthpicker-calendar-container">',
    '<div class="monthpicker-calendar"></div>',
    '<div class="monthpicker-footer">',
    '<button class="btn-flat monthpicker-clear waves-effect" style="visibility: hidden;" type="button"></button>',
    '<div class="confirmation-btns">',
    '<button class="btn-flat monthpicker-cancel waves-effect" type="button"></button>',
    '<button class="btn-flat monthpicker-done waves-effect" type="button"></button>',
    '</div>',
    '</div>',
    '</div>',
    '</div>',
    '</div>'
  ].join('');

  M.Monthpicker = Monthpicker;

  if (M.jQueryLoaded) {
    M.initializeJqueryWrapper(Monthpicker, 'monthpicker', 'M_Monthpicker');
  }

})(cash);

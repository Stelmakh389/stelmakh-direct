/* Лёгкая замена IMask для демо-копии: маска +7(000)000-00-00 */
(function () {
  function IMask(input, opts) {
    var self = this;
    this.el = input;
    this.masked = { isComplete: false };
    function format() {
      var d = input.value.replace(/\D/g, '');
      if (d[0] === '8' || d[0] === '7') d = d.slice(1);
      d = d.slice(0, 10);
      var out = '+7';
      if (d.length) out += '(' + d.slice(0, 3);
      if (d.length >= 3) out += ')' + d.slice(3, 6);
      if (d.length >= 6) out += '-' + d.slice(6, 8);
      if (d.length >= 8) out += '-' + d.slice(8, 10);
      input.value = d.length ? out : '';
      self.masked.isComplete = d.length === 10;
    }
    input.addEventListener('input', format, true);
  }
  window.IMask = IMask;
})();

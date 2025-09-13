(function () {
  function formatCurrency(amount) {
    try {
      return new Intl.NumberFormat("vi-VN", {
        style: "currency",
        currency: "VND",
      }).format(amount);
    } catch (e) {
      return (Number(amount) || 0).toLocaleString("vi-VN") + " ₫";
    }
  }
  function formatNumber(number) {
    try {
      return new Intl.NumberFormat("vi-VN").format(number);
    } catch (e) {
      return (Number(number) || 0).toString();
    }
  }
  window.TroCurrency = window.TroCurrency || { formatCurrency, formatNumber };
})();

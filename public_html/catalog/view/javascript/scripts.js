//Yandex Ecommerce
function sendYandexEcommerce(array, action) {
  if(typeof dataLayer == 'undefined')
      return false;

  var product = [{
    "id": array['id'],
    "name": array['name'],
    "price": array['price'],
    "category": array['category'],
    "quantity": array['quantity'],
    "variant" : array['variant']
  }];

  switch (action) {
    case "add": dataLayer.push({"ecommerce": {"currencyCode": "RUB", "add": {"products": product}}}); break;
    case "remove": dataLayer.push({"ecommerce": {"currencyCode": "RUB", "remove": {"products": product}}}); break;
    case "detail":
      delete product[0]["quantity"];
      dataLayer.push({"ecommerce": {"currencyCode": "RUB", "detail": {"products": product}}});
      break;
  }
}

$(document).ready(function(){

  function restrict_value(obj) {
    obj.value=Math.max(Math.min(obj.value, obj.max), obj.min);
  }

  function set_price(obj, price, quantity) {
    obj.text(new Intl.NumberFormat('ru-RU', { style: 'currency', currency: 'RUB' }).format(price * quantity).replace(/\D00(?=\D*$)/, ''));
  }

  $('.product-js .btn-minus').each(function() {
    $(this).click(function() {
      $(this).next().val(function(i, oldval) {
        return (oldval - 1 < 1 ? 1 : --oldval);
      }).change();
      var price = $(this).parents('.product-js').find('.price');
      set_price(price, parseFloat(price.attr('data-price')), $(this).next().val());
    });
  });

  $('.product-js .btn-plus').each(function() {
    $(this).click(function() {
      $(this).prev().val(function(i, oldval) {
        return ++oldval;
      }).change();
      var price = $(this).parents('.product-js').find('.price');
      set_price(price, parseFloat(price.attr('data-price')), $(this).prev().val());
    });
  });

  $('.product-js input[name=quantity]').each(function() {
    var ts;
    $(this).on('focusout', function() {
      $(this).val(function(i, oldval) {
        return (oldval < 1 ? 1 : oldval);
      }).change();
      var price = $(this).parents('.product-js').find('.price');
      set_price(price, parseFloat(price.attr('data-price')), $(this).val());
    }).trigger('focusout').on('mousewheel', function(event) {
      if (event.deltaY > 0) {
        $(this).next().click();
      } else {
        $(this).prev().click();
      }
      event.preventDefault();
    });
  });

  $('#cart > button').click(function(event) {
    $('#cart').toggleClass('open');
  });

  $('body').click(function(e) {
    var container = $("#cart");

    // if the target of the click isn't the container nor a descendant of the container
    if (!container.is(e.target) && container.has(e.target).length === 0) 
    {
        container.removeClass('open');
    }
  });

  $('body').on('click', '#cart .btn-minus', function() {
    $(this).next().val(function(i, oldval) {
      return (oldval - 1 < 1 ? 1 : --oldval);
    }).change();
  });

  $('body').on('click', '#cart .btn-plus', function() {
    $(this).prev().val(function(i, oldval) {
      return ++oldval;
    }).change();
  });

  $('body').on('change', '#cart input[name=quantity]', $.debounce(300, function() {
    $(this).val(function(i, oldval) {
      return (oldval < 1 ? 1 : oldval);
    });
    cart.update_product($(this).data('product'), $(this).val());
  }));


  $('body').on('click', '#cart .button-remove', function() {
    var c = confirm("Вы действительно хотите удалить товар из корзины?");
    if (c == true) {
      cart.remove($(this).data('product'), $(this).data('productid'));
    }
  });

  $('.sort-block .btn-sort').click(function() {
    $(this).toggleClass('active');
    $(this).next().toggleClass('active');
  });
  
});
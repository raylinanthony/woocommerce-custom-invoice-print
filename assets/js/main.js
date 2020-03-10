(function($) {
 $(function() {
    console.log('Shipping Labels works!')
    $('.generate_ship_labels').on('click', function(e){
        e.preventDefault();

        let input_ship_num = $('.ship_label_field_ray').val(); 

        if(input_ship_num == 0) return false;

        let _url  = '?ship_labels='+input_ship_num+'&order_id='+$(this).attr('data-order');

         window.open(_url, '_blank'); 
    }); 

 })
})(jQuery);
//limit the text
var MyUtils = {
    truncate: function(element, length){
        element = $(element);
        return element.update(element.innerHTML.truncate(length));
    },
    updateAndMark: function(element, html){
        return $(element).update(html).addClassName('updated');
    }
}
Element.addMethods(MyUtils);

//apply cufon fonts
Cufon.replace('.tittle span,.like-midd strong', {
    fontFamily: 'Helvetica Neue'
});
Cufon.replace(
    '.heading-content h2,' +
    '.footer h3,' +
    '.banner-content .login-form .login-content h2,' +
    '.login-form .login-content button.login span,' +
    '.login-form .login-content button.login span,' +
    '.login-form .login-content p.view-more a,' +
    '.toolbar h1',
    {
        fontFamily: 'Helvetica Neue Medium'        
    }
    );
Cufon.replace('.navigation ul li a',{
    hover:true
});

function stop_add_to_cart() {
    $$('.btn-cart').each(function(element){
        $(element).writeAttribute('onclick','javascript:return false;');
        $(element).observe('click',function(ele){
            Modalbox.show($('not-available'), {title: this.title, width: 300});
        });
    });
}
document.observe("dom:loaded", function() {  
    //stop add-to-cart functionality
    stop_add_to_cart();
});

Ajax.Responders.register({
  onComplete: function(){
    //stop add-to-cart functionality
    stop_add_to_cart();
  }
});
/*
//set min-width of all buttons
function set_width() {
    $$('.button > span > span').each(function(element){
        var tmp = $(element).getWidth();
    
        if(tmp==0) {
            $('no-display').innerHTML = '';
            var clone = $(element).up('button').clone(true);
            $('no-display').insert($(clone));
            tmp = $$('#no-display button > span > span')[0].getWidth();
        }
        $(element).up('button').setStyle({width: (tmp+11)+'px'});
    });
}


Ajax.Responders.register({
  onComplete: function(){
    set_width();
  }
});

document.observe("dom:loaded", function() {    
    set_width();
});
*/
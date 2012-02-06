/*
Cette création est mise à disposition selon le Contrat Paternité-Partage des Conditions Initiales à l'Identique 3.0 Unported disponible en ligne http://creativecommons.org/licenses/by-sa/3.0/ ou par courrier postal à Creative Commons, 171 Second Street, Suite 300, San Francisco, California 94105, USA.
*/
/* extension prototype */
Object.extend(Event, {
        wheel:function (event){
        var delta = 0;
        if (event.wheelDelta) {
			delta = event.wheelDelta/120;
			if (window.opera) delta = -delta;
        } else if (event.detail) {
            delta = -event.detail/3;
        }
		if(navigator.userAgent.indexOf('Mac') != -1) delta = -delta; 	 
		return Math.round(delta);
	}
});
var Horinaja = Class.create();
Horinaja.prototype = {
	initialize: function(capture, duree, secExecution, pagination){
		this.capture = capture;
		this.duree = duree;
		this.secExecution = secExecution;
		this.pagination = pagination;
		this.nCell = 0;
		this.id = 1;
		this.po = 0;	
		this.f = $$('div#'+this.capture+' ul li');
		this.px = $(this.capture).getWidth();
		this.pxH = $(this.capture).getHeight();
		this.start();
	},
	start: function(){		
				this.mover = $(this.capture).firstDescendant();	
				this.nCell = this.f.length;	
				for(i=0;i!=this.nCell;i++){
					this.f[i].setStyle({
					  width: this.px+'px',
					  height: (this.pxH-40)+'px',
					  float: 'left'
					  });	
				}
				$(this.capture).setStyle({
					overflow:'hidden',
					position: 'relative'
					});
				
				$(this.capture).firstDescendant().setStyle({
					width:(this.px*this.nCell)+'px'
					});	
				if(this.pagination){
						$('control-btn').insert({top:'<ol class="horinaja_pagination"></ol>'});
						this.olPagination  = $$('.horinaja_pagination')[0];
						/*$(this.capture).insert({bottom:'<ul class="horinaja_pagination" id="naja_pagination"></ul>'});
						this.olPagination  = $(this.capture).firstDescendant().next();*/
						$(this.olPagination).setStyle({
							//width: this.px+'px'
							});
						this.wb = Math.floor(this.px/this.f.length);
						for(i=1;i!=(this.f.length+1);i++){
							$(this.olPagination).insert({bottom:'<li><a id="'+i+'" href="javascript:void(0);" onClick="javascript:void(0);"></a></li>'}); /*px;*/
							if(i!=this.id){
								/*$(this.olPagination).childElements()[i-1].setStyle({
									opacity:0.2
									});					*/
							}
						}
				       	$(this.olPagination).childElements()[0].addClassName("active");
						$(this.olPagination).childElements()[0].setStyle({
							opacity:1
						});	
						this.startOC();
				}
				this.startPe();
				/*Event.observe($(this.capture),"mouseout", this.startPe.bind(this));
				Event.observe($(this.capture),"mouseover", this.stopPe.bind(this));								
				Event.observe($(this.capture), "mousewheel", this.wheelwheel.bind(this));		
				Event.observe($(this.capture), "DOMMouseScroll", this.wheelwheel.bind(this));				*/
				
				
	},
	startOC: function(){
	Event.observe($(this.olPagination),"click", this.moveP.bind(this));
	},
	startPe: function(){
	this.periodik = new PeriodicalExecuter(this.Pe.bind(this),this.secExecution);	
	},
	stopPe: function(){
	this.periodik.stop();
	},
	effaceP: function(mop){
		this.mop = mop;

		/*if(this.pagination)
		new Effect.Fade($(this.olPagination).childElements()[this.mop-1],{duration:0.3,to:0.2})*/
	},
	move: function(xp){
		this.xp = xp;
		new Effect.Move(this.mover, { 
					x: this.xp, 
					y: 0,
					mode:'absolute',
					duration: this.duree,
					transition: Effect.Transitions.sinoidal
				});
				if(this.pagination){
					new Effect.Appear($(this.olPagination).childElements()[this.id-1],{duration:0.3,to:1})
				}	
	},	
	Pe: function(){
					if(this.id<this.nCell){
							this.po=this.po-this.px;
							this.effaceP(this.id);
							this.id=this.id+1;														
							this.move(this.po);
							this.activepage(this.id);
						}else{
							this.po=0;
							this.effaceP(this.id);
							this.id=1;
							this.move(this.po);
							this.activepage(this.id);
						}
	},
	prPe: function(){
		              if(this.id==1)
					  {
						    this.po=-1*(this.px)*(this.nCell-1);
							this.effaceP(this.id);
							this.id=this.nCell;
							this.move(this.po);
							this.activepage(this.id);
					  }
					  else					  
					  {
						  
							this.po=this.po+this.px;
							this.effaceP(this.id);
							this.id=this.id-1;
							this.move(this.po);
							this.activepage(this.id);
						
					  }
					
					
	},
	activepage: function(pid){
		
		$(this.olPagination).childElements().each(function(element,index){
					$(element).removeClassName('active');
					
				});		
		$(this.olPagination).childElements()[pid-1].addClassName('active');
		
	},
	moveP: function(evt){
		var child = Event.element(evt);
		this.occ = parseInt(child.readAttribute("id"));        
		
		if(this.id>this.occ){
			this.diff= this.id-this.occ;
			this.po=this.po+(this.px*this.diff);
			this.effaceP(this.id);
			this.id=this.occ;
			this.move(this.po);
			this.activepage(this.id);
		}else if(this.id<this.occ){
			this.diff= this.occ-this.id;
			this.po=this.po-(this.px*this.diff);
			this.effaceP(this.id);
			this.id=this.occ;
			this.move(this.po);
			this.activepage(this.id);
		}
		
		
	},
	stopEvent:function(pE)
	{
	   if (!pE)
		 if (window.event)
		   pE = window.event;
		 else
		   return;
	  if (pE.cancelBubble != null)
		 pE.cancelBubble = true;
	  if (pE.stopPropagation)
		 pE.stopPropagation();
	  if (pE.preventDefault)
		 pE.preventDefault();
	} ,
	wheelwheel: function(e){
						this.event = e;
						this.stopPe();
						this.stopEvent(e);
						if (Event.wheel(this.event) < 0){
							if(this.id<this.nCell){
									this.po=this.po-this.px;
									this.effaceP(this.id);
									this.id=this.id+1;
									this.move(this.po);
								}
						}else{
							if(this.id!=1){
									this.po=this.po+this.px;
									this.effaceP(this.id);
									this.id=this.id-1;
									this.move(this.po);
								}
						}
	}
};
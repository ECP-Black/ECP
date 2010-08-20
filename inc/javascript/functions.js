window.addEvent('domready', function() {
	if(Browser.Engine.trident4) Browser.scanForPngs(document.body);
	tips = new Tips($$('.Tips'), {showDelay: 0});
	smooth = new SmoothScroll();
	$$('.Tips').each(function(e) {
		if (e.getElement('span.tipcontents')) {
			e.store('tip:text', e.getElement('span.tipcontents').get('html'));
		}
	});
	if(isNaN($('shoutbox_add'))) {
	    FormValidator.addAllThese([
		['validate-captcha-mini', 	{
			errorMsg:	'', 
			test: 	function(v) {		
						var myXHR = new Request({url: 'ajax_checks.php?func=check_captcha_mini&code=' + v.get('inputValue'), async: false}).send();
						if(myXHR.xhr.responseText == 0)
							reload_captcha_mini('captcha_shout_pic', 'chars=2&minsize=12&maxsize=16', 'shout_captcha'); 
						return (myXHR.xhr.responseText == 1)? true: false;
					}
			}]												
		]);			
		shout_form = new FormValidator($('shoutbox_add', {useTitles: true})); 			
		$('shoutbox_add').addEvent('submit', function(e) {
			if(shout_form.validate()) {
				new Event(e).stop();
				$('shout_spinner').style.display = '';
				$('shout_submit').disabled = true;	
				$('shoutbox_add').action += '&ajax=1';
				new Request({
					url: $('shoutbox_add').get('action'),
					onSuccess: function(r) {
						shout_form.start();
						$('shout_spinner').style.display = 'none';
						$('shout_submit').disabled = false;							
						if(r == 'ok') {			
							$('shoutbox_add').reset();	
							new Request.HTML({
								url: 'ajax_checks.php?func=get_shouts_mini', 
								useWaiter: true,
								onComplete: function() { 
										if(!Browser.Engine.trident)
										over_texts.repositionAll(); 
								}, 
								update: 'shoutbox_menu_container' 
							}).get();									
						} else {
							errorAlert(ERROR, r);
						}
					}
				}).send($('shoutbox_add'));
			}
		});	
		(function(){ 
			new Request.HTML({
				url: 'ajax_checks.php?func=get_shouts_mini', 
				useWaiter: true,
				onComplete: function() { 
						if(!Browser.Engine.trident)
						over_texts.repositionAll(); 
				}, 
				update: 'shoutbox_menu_container' 
			}).get();
		}).periodical(60000);	
	}
	if (isNaN($('random_pic'))) {
		(function(){ 
			new Request.HTML({
				url: 'ajax_checks.php?func=get_rand_pic&rand_ajax=1', 
				useWaiter: true,
				onComplete: function() { 
						if(!Browser.Engine.trident)
						over_texts.repositionAll(); 
				}, 				
				update: 'random_pic' 
			}).get();
		}).periodical(12000);	
	}
});
window.addEvent('load', function() {
	//if(Browser.Engine.trident4) $$('img.fixPNG').each(Browser.fixPNG);	
	over_texts = new OverText($$('.overtext'));	
});
function check_chars_length(el, max, span) {
	if(el.get('inputValue').length > max) {
		el.value = el.get('inputValue').substring(0, max);
	}
	span.set('html', max-el.get('inputValue').length);
}
function set_flagge(id) {
	$('flagge').src = 'images/flaggen/'+id.options[id.selectedIndex].value+'.gif';
	$('flagge').alt = id.options[id.selectedIndex].text;
	$('flagge').title = id.options[id.selectedIndex].text;
}
function load_com_page(bereich, id, seite) {
	new Request({
						 onRequest: 	function() {
											waiterExample = new Waiter($('comments_bereich')).start();
										},
						 onSuccess: 	function(e) {
						 					waiterExample.stop();
											$('comments_bereich').set('html', e);	
											smooth = new SmoothScroll();				 					
											$('comments_bereich').setStyle('opacity', 0);
										 	new Fx.Morph($('comments_bereich')).start({
												'opacity': [0, 1]
											});									 									
										}
						}).get('ajax_checks.php', "func=getcomments&bereich="+bereich+"&id="+id+"&page="+seite);
	return false;
}
function load_forum_com_page(id, bid, seite, order) {
	new Request({
						 onRequest: 	function() {
											waiterExample = new Waiter($('comments_bereich')).start();
										},
						 onSuccess: 	function(e) {
						 					waiterExample.stop();
											$('comments_bereich').set('html', e);	
											smooth = new SmoothScroll();
											scroll_to($('content'),500);
											new Lightbox();	
			 													 									
										}
						}).get('ajax_checks.php', "func=get_forum_comments&boardID="+bid+"&threadID="+id+"&page="+seite+"&order="+order);
	return false;
}
function make_info(text, img) {
	new StickyWinFx({
		content: StickyWin.ui(INFO, '<img style="vertical-align: middle;" src="templates/'+DESIGN+'/images/'+img+'.png" alt="" title="" /> '+text, {
			width: '400px',
			fadeDuration: 600,
			draggable: true,
		    buttons: [
		      {
		        text: CLOSE
		      }
		    ]
		})
	});
}
function check_color(feld, sta) {
	var i = 0;
	var pos = 0;
	$$('#'+feld + ' tr').each( function (e) {
		if(typeof(sta) != undefined && sta > pos) { 
			pos++; return; 
		} 
		e.removeClass('row_odd'); 
		e.removeClass('row_even'); 			
		if(i%2) {
			e.addClass('row_even');
		} else {
			e.addClass('row_odd');
		}
		i++;
	});
}
function selected_value(el) {
	return $(el).options[$(el).selectedIndex].value;
}
function scroll_to(feld, speed) {
	new Fx.Scroll(window, {transition: Fx.Transitions.Elastic.easeOut, duration: speed}).toElement(feld);
}
function load_wars(gameid, teamid, matchtypeid, xonx, sortby, art, seite) {
	new Request.JSON({url: "ajax_checks.php?func=get_clanwars&gameID="+gameid+"&teamID="+teamid+"&matchtypeID="+matchtypeid+"&xonx="+xonx+"&sortby="+sortby+"&art="+art+"&page="+seite, 
		onRequest: function () {  
			waitercw.start(); 
		}, 
		onSuccess: function(data) {
			waitercw.stop();
			if(typeof(data.error) === 'undefined') { 
				$('clanwars_overview').set('html', data.clanwars);
				$('clanwars_score').set('html', data.score);
			} else {
				errorAlert(ERROR, data.error);
			}
		}
	}).get();
	return false;
}
function reload_captcha(feld, qstr, inp) {
	datum = new Date();
	$(feld).src = 'captcha.php?'+qstr+'&time='+datum.getTime();
	$(inp).value = '';
}
function reload_captcha_mini(feld, qstr, inp) {
	datum = new Date();
	$(feld).src = 'captcha.php?mode=mini&'+qstr+'&time='+datum.getTime();
	$(inp).value = '';
}
function submit_survey(id, max) {
	if(max > 1) {
		antworten = 0;
		$('div_survey_'+id).getElements('input').each(function(e) { 
			if(e.checked) antworten++;
		});
		if (antworten == 0) {
			errorAlert(ERROR, SURVEY_MAKE_A_CHOOSE);
		} else if (antworten > max) {
			errorAlert(ERROR, SURVEY_TOO_MANY.replace('{anzahl}', max));
		} else {
			$('survey_'+id).action= '?section=survey&action=vote&id=' + id + '&ajax=1';
			new Request({
				url: $('survey_'+id).action,
				onRequest: function(){
					$('survey_submit_' + id).disabled = true;
					waiter = new Waiter($('div_survey_' + id)).start();
				},
				onSuccess: function(r){
					if($('survey_anzahl_'+id).get('text').contains('.')) {
						zahl = $('survey_anzahl_'+id).get('text').replace('.','').toInt();
					}  else {
						zahl = $('survey_anzahl_'+id).get('text').toInt();
					}
					zahl += antworten;
					$('survey_anzahl_'+id).set('text',zahl);
					if (r == 'ok') {
						new Request.HTML({url: 'ajax_checks.php?func=get_survey&id=' + id, 
							update: 'div_survey_' + id,
							evalScripts: true,
							onComplete: function(r) {
								waiter.stop();
							}
						}).get();
					}
					else {
						errorAlert(ERROR, r);
					}
				}
			}).send($('survey_'+id));	
		}
	} else {
		answerid = 0;
		$('div_survey_'+id).getElements('input').each(function(e) { if(e.checked) answerid = e.value; });
		if (answerid) {
			new Request({url: '?section=survey&action=vote&id=' + id + '&ajax=1', 
				onRequest: function(){
					$('survey_submit_' + id).disabled = true;
					waiter = new Waiter($('div_survey_' + id)).start();
				},
				data: 'answer=' + answerid,
				onSuccess: function(r){
					if (r == 'ok') {
						if($('survey_anzahl_'+id).get('text').contains('.')) {
							zahl = $('survey_anzahl_'+id).get('text').replace('.','').toInt();
						}  else {
							zahl = $('survey_anzahl_'+id).get('text').toInt();
						}
						zahl++;
						$('survey_anzahl_'+id).set('text',zahl);
						new Request.HTML({url: 'ajax_checks.php?func=get_survey&id=' + id, 
							update: 'div_survey_' + id,
							evalScripts: true,
							onComplete: function(r) {
								waiter.stop();
							}							
						}).get();
					}
					else {
						errorAlert(ERROR, r);
					}
				}
			}).post();
		} else {
			errorAlert(ERROR, SURVEY_MAKE_A_CHOOSE);
		}
	}
	return false;
}
function submit_mini_survey(id, max) {
	if(max > 1) {
		antworten = 0;
		$('survey_mini_form').getElements('input').each(function(e) { 
			if(e.checked) antworten++;
		});
		if (antworten == 0) {
			errorAlert(ERROR, SURVEY_MAKE_A_CHOOSE);
		} else if (antworten > max) {
			errorAlert(ERROR, SURVEY_TOO_MANY.replace('{anzahl}', max));
		} else {
			new Request({
				url: '?section=survey&action=vote&id=' + id + '&ajax=1',
				onRequest: function(){
					$('survey_mini_submit1').disabled = true;
				},
				onSuccess: function(r){
					$('survey_mini_submit1').disabled = false;					
					if (r == 'ok') {
						new Request.HTML({url: 'ajax_checks.php?func=get_survey&id=' + id+'&mini=1', 
							update: 'survey_mini_div',
							evalScripts: true,
							useWaiter: true
						}).get();
					}
					else {
						errorAlert(ERROR, r);
					}
				}
			}).send($('survey_mini_form'));	
		}
	} else {
		answerid = 0;
		$('survey_mini_form').getElements('input').each(function(e) { if(e.checked) answerid = e.value; });
		if (answerid) {
			new Request({url: '?section=survey&action=vote&id=' + id + '&ajax=1', 
				onRequest: function(){
					$('survey_mini_submit1').disabled = true;
				},
				data: 'answer=' + answerid,
				onSuccess: function(r){
					$('survey_mini_submit1').disabled = false;
					if (r == 'ok') {
						new Request.HTML({url: 'ajax_checks.php?func=get_survey&id=' + id + '&mini=1', 
							update: 'survey_mini_div',
							evalScripts: true,
							useWaiter: true
						}).get();
					}
					else {
						errorAlert(ERROR, r);
					}
				}
			}).post();
		} else {
			errorAlert(ERROR, SURVEY_MAKE_A_CHOOSE);
		}
	}
	return false;
}
function forum_toggle_kate(id, img) {
	toggles[id].toggle();
	if (typeof(Cookie.read('foren[' + id + ']')) == 'undefined' || Cookie.read('foren[' + id + ']') == 'open') {
		Cookie.write('foren[' + id + ']', 'closed', {
			duration: 365
		});
		new Asset.image('templates/'+DESIGN+'/images/plus.png');
		img.src= 'templates/'+DESIGN+'/images/plus.png';
	} else {
		Cookie.write('foren[' + id + ']', 'open', {
			duration: 365
		});
		new Asset.image('templates/'+DESIGN+'/images/plus.png');
		img.src= 'templates/'+DESIGN+'/images/minus.png';		
	}
	return false;
}
function survey_add_answer(feld) {
	feld = feld.parentNode;
	neues = new Element('div', {'class' : 'answer'});
	neues.innerHTML = feld.innerHTML;
	neues.injectAfter(feld);
	if (neues.getChildren().length >= 4) {
		neues.getFirst().getNext().getNext().name = 'v_'+(Math.random() * 100).toInt();
		neues.getFirst().getNext().getNext().getNext().onclick = new Function('F','survey_del_answer(this)');
	}	
	neues.getFirst().getNext().value = '';	
	neues.getFirst().getNext().name = 'answer_';
	nr = 1;
	$$('div .answer').each(function(e) {
		e.getFirst().set('text',e.getFirst().get('text').replace(/\d+/, nr));
		if (!e.getFirst().getNext().name.contains('old')) {
			e.getFirst().getNext().name = 'answer_' + nr;
			e.getFirst().getNext().id = 'answer_' + nr;
		}
		nr++;
	});
}
function survey_del_answer(feld) {
	feld = feld.parentNode;
	if ($$('div .answer').length > 1) {
		slide = new Fx.Slide(feld);
		slide.slideOut().chain(function(e){
			feld.destroy();
			nr = 1;
			$$('div .answer').each(function(elm){
				elm.getFirst().set('text',elm.getFirst().get('text').replace(/\d+/, nr));
				elm.getFirst().getNext().name = 'answer_' + nr;
				elm.getFirst().getNext().id = 'answer_' + nr;
				nr++;				
			});
		});
	}
}
function forum_survey_answers(id) {
	new Request.HTML({url: 'ajax_checks.php?func=get_forum_survey&id=' + id, 
			update: 'forum_survey',
			evalScripts: true,
			useWaiter: true
	}).get();
}
function submit_forum_survey(id, max) {
	if(max > 1) {
		antworten = 0;
		$('div_survey_'+id).getElements('input').each(function(e) { 
			if(e.checked) antworten++;
		});
		if (antworten == 0) {
			errorAlert(ERROR, SURVEY_MAKE_A_CHOOSE);
		} else if (antworten > max) {
			errorAlert(ERROR, SURVEY_TOO_MANY.replace('{anzahl}', max));
		} else {
			$('survey_'+id).action= '?section=forum&action=survey_vote&id=' + id + '&ajax=1';
			new Request({
				url: $('survey_'+id).action,
				onRequest: function(){
					$('survey_submit_' + id).disabled = true;
					waiter = new Waiter($('forum_survey')).start();
				},
				onSuccess: function(r){
					if (r == 'ok') {
						new Request.HTML({url: 'ajax_checks.php?func=get_forum_survey&id=' + id, 
							update: 'forum_survey',
							evalScripts: true,
							onComplete: function(r) {
								waiter.stop();
							}
						}).get();
					}
					else {
						errorAlert(ERROR, r);
						waiter.stop();
						$('survey_submit_' + id).disabled = false;						
					}
				}
			}).send($('survey_'+id));	
		}
	} else {
		answerid = 0;
		$('div_survey_'+id).getElements('input').each(function(e) { if(e.checked) answerid = e.value; });
		if (answerid) {
			new Request({url: '?section=forum&action=survey_vote&id=' + id + '&ajax=1', 
				onRequest: function(){
					$('survey_submit_' + id).disabled = true;
					waiter = new Waiter($('forum_survey')).start();
				},
				data: 'answer=' + answerid,
				onSuccess: function(r){
					if (r == 'ok') {
						new Request.HTML({url: 'ajax_checks.php?func=get_forum_survey&id=' + id, 
							update: 'forum_survey',
							evalScripts: true,
							onComplete: function(r) {
								waiter.stop();
							}
						}).get();
					}
					else {
						errorAlert(ERROR, r);
						waiter.stop();
						$('survey_submit_' + id).disabled = false;
					}
				}
			}).post();
		} else {
			errorAlert(ERROR, SURVEY_MAKE_A_CHOOSE);
		}
	}
	return false;
}
function open_sub_menu(id, relelm) {
$$('body')[0].removeEvents('click');
if (typeof(laststickywin) == 'undefined' || laststickywin != id || typeof(stickywindow) == 'undefined' || !stickywindow.visible) {
	stickywindow = new StickyWinFx({
		content: $(id).innerHTML,
		fadeDuration: 200,
		relativeTo: relelm,
		position: 'center',
		className: 'comments_menu',
		offset: {
			x: 0,
			y: 25
		},
		useIframeShim: true
	});
}
laststickywin = id;
(function () { $$('body')[0].addEvent('click', function(e) {
	if(typeof(stickywindow) != 'undefined' || stickywindow.visible) {
		stickywindow.hide();
	}
});}).delay(250);
}
function thread_pin(id) {
	new Request({url: 'ajax_checks.php?func=forum_sticky&id=' + id, 
		onSuccess: function(r){
			if (r == '1' || r == '0') {
				if (r == '1') {
					make_info(FORUM_THREAD_PIN, 'accept');
					$('pin_icon').style.display = '';
					$('thread_admin').value = $('thread_admin').get('inputValue').replaceAll(FORUM_A_THREAD_PIN, FORUM_A_THREAD_UNPIN);
				} else {
					make_info(FORUM_THREAD_UNPIN, 'accept');
					$('pin_icon').style.display = 'none';					
					$('thread_admin').value = $('thread_admin').get('inputValue').replaceAll(FORUM_A_THREAD_UNPIN, FORUM_A_THREAD_PIN);
				}
			}
			else {
				errorAlert(ERROR, r);
			}
		}
	}).get();
}
function thread_close(id) {
	new Request({url: 'ajax_checks.php?func=forum_close&id=' + id, 
		onSuccess: function(r){
			if (r == '1' || r == '0') {
				if (r == '1') {
					make_info(FORUM_THREAD_CLOSED, 'accept');
					$('closed_icon').style.display = '';
					$$('span.thread_answer').each(function(e) { e.style.display = 'none'; });					
					$('thread_admin').value = $('thread_admin').get('inputValue').replaceAll(FORUM_A_THREAD_CLOSED, FORUM_A_THREAD_OPEN);
				} else {
					make_info(FORUM_THREAD_OPEN, 'accept');
					$('closed_icon').style.display = 'none';	
					$$('span.thread_answer').each(function(e) { e.style.display = ''; });				
					$('thread_admin').value = $('thread_admin').get('inputValue').replaceAll(FORUM_A_THREAD_OPEN, FORUM_A_THREAD_CLOSED);
				}
			}
			else {
				errorAlert(ERROR, r);
			}
		}
	}).get();
}
function thread_delete(id, bid, name) {
	if(id != 0) {
		new StickyWin({
		  content: StickyWin.ui(CONFIRM, DEL_THREAD.replace('{name}', name), {
		    width: '500px',
		    buttons: [
		      {
		        text: YES,
				onClick: function() {
					new Request({url: 'ajax_checks.php?func=del_thread&id='+id, 
								onSuccess: function(r) {
									if(r == 'ok') {
										location.href="?section=forum&action=board&boardID="+bid;										
									} else {
										errorAlert(ERROR, r);
									}
								}	
								}).get();			
				}
		      },
		      {
		        text: NO 
		      }
		    ]
		  })
		});
	return false;
	}	
}
function goto_board(el) {
	value = el.options[el.selectedIndex].value;
	if (value == 'index') {
		location.href = '?section=forum';
	} else if (value.contains('_sub')) {
		location.href = '?section=forum&action=subboard&boardID=' + value.toInt();
	} else if (value != '-1') {
		location.href = '?section=forum&action=board&boardID=' + value;
	}
}
function thread_move(id) {
		change_board_win = new StickyWinFxModal({
		  draggable: true,
		  fadeDuration: 700,
		  onClose: function () { this.destroy(); },
		  content: StickyWin.ui(THREAD_MOVE, '<div id="thread_move_div"><center><img src="templates/'+DESIGN+'/images/spinner.gif"></center></div>', {
			width: '600px'
		  })
		});
		new Request.HTML({url: 'ajax_checks.php?func=thread_move&id='+id, 
			update: 'thread_move_div', 
			evalScripts: true	
		}).get();	
	return false;	
}
function forum_delete_attach(id, name) {
	if(id != 0) {
		new StickyWin({
		  content: StickyWin.ui(CONFIRM, DEL_ATTACH.replace('{name}', name), {
		    width: '500px',
		    buttons: [
		      {
		        text: YES,
				onClick: function() {
					new Request({url: 'ajax_checks.php?func=del_attach&id='+id, 
								onSuccess: function(r) {
									if(r == 'ok') {
										new Fx.Morph('attach_' + id, {
											duration: 800,
											onComplete: function(){
												$('attach_' + id).destroy();
											}
										}).start({'opacity': [1,0]}); 											
									} else {
										errorAlert(ERROR, r);
									}
								}	
								}).get();				
				}
		      },
		      {
		        text: NO 
		      }
		    ]
		  })
		});
	return false;
	}	
}
function thread_rating(val) {
	new Request({url: 'ajax_checks.php?func=thread_vote&wert='+val+'&id='+Browser.getQueryStringValue('threadID').toInt(), 
			onRequest: function () {
				thread_r.locked = true;	
				thread_r.options.callback = null;
			},
			onSuccess: function(r) {
				if(r.length <= 3) {
					thread_r.locked = false;
					if(r.length == 3) {
						thread_r.setValue(r.toFloat());
					} else {
						thread_r.setValue(r.toInt());
					}
					thread_r.locked = true;
					make_info(FORUM_RATING_SUCCESS, 'accept');
				} else {
					errorAlert(ERROR, r);
					thread_r.locked = false;
				}
			}	
	}).get();	
}
function forum_survey_edit(id) {
		survey_edit_win = new StickyWinFx({
		  draggable: true,
		  fadeDuration: 700,
		  onClose: function () { this.destroy(); },
		  content: StickyWin.ui(SURVEY_EDIT, '<div id="survey_edit_div"><center><img src="templates/'+DESIGN+'/images/spinner.gif"></center></div>', {
			width: '600px'
		  })
		});
		new Request.HTML({url: 'ajax_checks.php?func=thread_survey_edit&id='+id, 
			update: 'survey_edit_div', 
			evalScripts: true	
		}).get();	
	return false;	
}
function forum_answer_edit(id) {
		answer_edit_win = new StickyWinFxModal({
		  draggable: true,
		  fadeDuration: 700,
		  onClose: function () { this.destroy(); },
		  content: StickyWin.ui(SURVEY_ANSWER_EDIT, '<div id="answer_edit_div"><center><img src="templates/'+DESIGN+'/images/spinner.gif"></center></div>', {
			width: '600px'
		  })
		});
		new Request.HTML({url: 'ajax_checks.php?func=thread_answer_edit&id='+id, 
			update: 'answer_edit_div', 
			evalScripts: true	
		}).get();	
	return false;	
}
function forum_answer_add(id) {
		answer_add_win = new StickyWinFxModal({
		  draggable: true,
		  fadeDuration: 700,
		  onClose: function () { this.destroy(); },
		  content: StickyWin.ui(SURVEY_ANSWER_ADD, '<div id="answer_add_div"><center><img src="templates/'+DESIGN+'/images/spinner.gif"></center></div>', {
			width: '600px'
		  })
		});
		new Request.HTML({url: 'ajax_checks.php?func=thread_answer_add&id='+id, 
			update: 'answer_add_div', 
			evalScripts: true	
		}).get();	
	return false;	
}
function forum_answer_delete(id, sid, name) {
	if(id != 0) {
		new StickyWin({
		  content: StickyWin.ui(CONFIRM, SURVEY_ANSWER_DELETE1.replace('{antwort}', name), {
		    width: '500px',
		    buttons: [
		      {
		        text: YES,
				onClick: function() {
					new Request({url: 'ajax_checks.php?func=thread_answer_del&id='+id, 
								onSuccess: function(r) {
									if(r == 'ok') {
										forum_survey_answers(sid);											
									} else {
										errorAlert(ERROR, r);
									}
								}	
								}).get();				
				}
		      },
		      {
		        text: NO 
		      }
		    ]
		  })
		});
	return false;
	}	
}
function forum_survey_delete(id) {
	if(id != 0) {
		new StickyWin({
		  content: StickyWin.ui(CONFIRM, DEL_SURVEY, {
		    width: '500px',
		    buttons: [
		      {
		        text: YES,
				onClick: function() {
					new Request({url: 'ajax_checks.php?func=thread_survey_del&id='+id, 
								onSuccess: function(r) {
									if(r == 'ok') {
										new Fx.Morph('forum_survey', {
											duration: 800,
											onComplete: function(){
												$('forum_survey').destroy();
											}
										}).start({
											'opacity': [1, 0]
										}); 											
									} else {
										errorAlert(ERROR, r);
									}
								}	
								}).get();				
				}
		      },
		      {
		        text: NO 
		      }
		    ]
		  })
		});
	return false;
	}	
}
function forum_abo(id) {
	new Request({url: 'ajax_checks.php?func=forum_abo&id=' + id,
		onSuccess: function(r){
			if (r == '1' || r == '0') {
				if (r == '1') {
					$('link_abo').set('text',FORUM_ABO_DEL);
				} else {
					$('link_abo').set('text',FORUM_ABO);				}
			}
			else {
				errorAlert(ERROR, r);
			}
		}
	}).get();
}
function server_refresh(id) {
	load_content('server_'+id, '?section=server&ajax=1&id='+id);
}
function load_clankasse_page(page) {
	load_content('kasse_trans', 'ajax_checks.php?func=clankasse&page='+page);	
	return false;
}
function monat_user_buchung(geld, monat, user, id, amonat, ajahr) {
		new StickyWin({
		  content: StickyWin.ui(CONFIRM, BUCH_FAST_USER.replace('{user}', user).replace('{geld}', geld).replace('{monat}', monat), {
		    width: '500px',
		    buttons: [
		      {
		        text: YES,
				onClick: function() {
					new Request.HTML({url: 'ajax_checks.php?func=admin&site=buch_monat_user&id='+id+'&monat='+monat+'&startj='+ajahr+'&startm='+amonat, 
									useWaiter: true,
									update: $('clankasse_overview'),
									onComplete: function(r) {
										load_clankasse_page(1);
									}	
								}).get();				
				}
		      },
		      {
		        text: NO 
		      }
		    ]
		  })
		});
	return false;	
}
function load_clankasse_overview(monat, jahr) {
	load_content('clankasse_overview', 'ajax_checks.php?func=clankasse_overview&monat='+monat+'&jahr='+jahr);		
}
function load_links(seite) {
	load_content('weblinks', 'ajax_checks.php?func=get_links&page='+seite);
	return false;	
}
function load_kate_page(id, seite) {
	load_content('kate_overview', 'ajax_checks.php?func=get_galleries&id='+id+'&page='+seite);	
	return false;	
}
function load_shout_page(seite) {
	load_content('shout_overview', 'ajax_checks.php?func=shout_page&page='+seite);		
	return false;	
}
function load_gallery_page(id, seite) {
	load_content('gallery_pictures', 'ajax_checks.php?func=get_pictures&id='+id+'&page='+seite);
	return false;	
}
function load_gallery_pic(id) {
	new Request.HTML({url: 'ajax_checks.php?func=get_pic&id='+id, 
				useWaiter: true,
				update: $('display_pic'),
				onComplete: function(){
					new Lightbox();
					if (isNaN($('comments_bereich'))) {
						load_com_page('gallery', id, 1);
						if (isNaN($('comments_add')))
						$('comments_add').action = '?section=gallery&action=addcomment&id=' + id;
					}
				}
	}).get();
	return false;	
}
function mark_all(feld, val) {
	if(val.checked == true) val = true; else val = false;
	$$('#'+feld+' input').each(function(e) {
		e.checked = val;
	});
}
function read_all() {
	new Request({url: 'ajax_checks.php?func=read_all',
				onSuccess: function(r){
					if(r == 'ok') {
						$$('#table_msgin img').each(function(e) {
							if(e.src.contains('new')) e.destroy();
						});
					} else {
						errorAlert(ERROR, r);
					}
				}
	}).get();
}
function del_message(id, mode) {
		new StickyWinFx({
		  draggable: true,
		  content: StickyWin.ui(CONFIRM, DEL_MESSAGE, {
		    width: '500px',
		    buttons: [
		      {
		        text: YES,
				onClick: function() {
					new Request({
						url:'ajax_checks.php?func=account_del_msg&id='+id, 
									onSuccess: function(r) {
										if(r == 'ok') {
											var morph = new Fx.Morph('msg_'+id, {
													duration:800, 
													onComplete: function() {
														$('msg_'+id).destroy();
														check_color('table_msg'+mode, 1);
													}
											});
											morph.start({'opacity': [1,0]});
										} else {
											errorAlert(ERROR, r);
										}
									}	
								}).get();				
				}
		      },
		      {
		        text: NO 
		      }
		    ]
		  })
		});
	return false;	
}
function ask_massdel(form) {
		new StickyWin({
		  content: StickyWin.ui(CONFIRM, DEL_ALL_MESSAGES, {
		    width: '500px',
		    buttons: [
		      {
		        text: YES,
				onClick: function() {
					form.submit();			
				}
		      },
		      {
		        text: NO 
		      }
		    ]
		  })
		});
}
function send_message_user(id, username, title) {
	if(typeof(msg_win) == 'undefined') msg_win = {};
	if(typeof(msg_win[id]) == 'undefined') {
		msg_win[id] = new StickyWinFx({
		  draggable: true,
		  fadeDuration: 700,
		  onClose: function () {  }, 
		  content: StickyWin.ui(SEND_MESSAGE.replace('{username}', username), '<div id="msg_to_user_'+id+'"><center><img src="templates/'+DESIGN+'/images/spinner.gif"></center></div>', {
			width: '600px'
		  })
		});
		var myAjax = new Request.HTML({
			url: 'ajax_checks.php?func=new_msg&id='+id,
			update: 'msg_to_user_'+id, 
			evalScripts: true, 
			onComplete: function() { 		
				if(typeof(title) != 'undefined') {
					if(title.contains('RE:')) {
						$('title_'+id).value = title;
					} else {
						$('title_'+id).value = 'RE: '+title;						
					}
				}
			}
			}).get();
	} else {
		msg_win[id].show();
		if(typeof(title) != 'undefined') {
			if(title.contains('RE:')) {
				$('title_'+id).value = title;
			} else {
				$('title_'+id).value = 'RE: '+title;						
			}
		}		
	}
	return false;
}
function toggle_buddy(id, img) {
	toggles[id].toggle();
	if (typeof(Cookie.read('buddy[' + id + ']')) == 'undefined' || Cookie.read('buddy[' + id + ']') == 'open') {
		Cookie.write('buddy[' + id + ']', 'closed', {
			duration: 365
		});
		new Asset.image('templates/'+DESIGN+'/images/plus.png');
		img.src= 'templates/'+DESIGN+'/images/plus.png';
	} else {
		Cookie.write('buddy[' + id + ']', 'open', {
			duration: 365
		});
		new Asset.image('templates/'+DESIGN+'/images/minus.png');
		img.src= 'templates/'+DESIGN+'/images/minus.png';		
	}
	return false;
}
function del_from_buddy(id, username) {
		new StickyWin({
		  content: StickyWin.ui(CONFIRM, DEL_BUDDY.replace('{name}', username), {
		    width: '500px',
		    buttons: [
		      {
		        text: YES,
				onClick: function() {
					new Request({url: 'ajax_checks.php?func=del_buddy&id='+id, 
									onSuccess: function(r) {
										if(r == 'ok') {
											new Fx.Morph('buddy_' + id, {
												duration: 800,
												onComplete: function(){
													$('buddy_' + id).destroy();
													check_color('buddy_table', 1);
												}
											}).start({'opacity': [1,0]}); 
										} else {
											errorAlert(ERROR, r);
										}
									}	
								}).get();				
				}
		      },
		      {
		        text: NO 
		      }
		    ]
		  })
		});
	return false;	
}	
function add_buddy(id) {
	new Request({url: 'ajax_checks.php?func=add_buddy&id='+id, 
		onSuccess: function(r) {
			if(r.toInt() == 1) {
				make_info(BUDDY_ADD_SUCCESS, 'accept');
			} else if (r.toInt() == 2) {
				make_info(BUDDY_ALLREADY, 'cancel');
			} else if (r.toInt() == 3) {
				make_info(BUDDY_YOURSELF, 'cancel');				
			} else {
				errorAlert(ERROR, r);
			}
		}	
	}).get();	
}
function load_teamspeak_info(a, id, mode) {
	new Request.HTML({url: '?section=teamspeak&ajax=1&cID='+id+'&type='+mode, 
				useWaiter: true,
				update: $('ts_info')
	}).get();
	return false;
}
function ts_login(chn, ip, port) {
	var myPopup = new Browser.Popup('module/teamspeak/login.php?cName='+chn+'&ip='+ip+'&port='+port, {
	    width: 200,
	    height: 210,
	    x: 500
	});
}
function load_user(link) {
	new Request.HTML({url: 'ajax_checks.php?func=get_user_list&'+link+'&orderby='+$('orderby').get('inputValue')+'&order='+$('order').get('inputValue'),
				useWaiter: true,
				update: $('user_liste'),
				onComplete: function(r) { 
					$$('#user_liste a.Tips').each(function(e) {
						tips.attach(e);
						if (e.getElement('span.tipcontents')) {
							e.store('tip:text', e.getElement('span.tipcontents').get('html'));
						}
					});
				}				
	}).get();
	return false;
}
function comment_delete(id, username) {
		new StickyWin({
		  content: StickyWin.ui(CONFIRM, DEL_COMMENT.replace('{username}', username), {
		    width: '500px',
		    buttons: [
		      {
		        text: YES,
				onClick: function() {
					new Request({url: 'ajax_checks.php?func=comments_del&id='+id, 
									onSuccess: function(r) {
										if(r == 'ok') {
											new Fx.Morph('com_'+id, {duration:800, onComplete: function () { 
												$('com_'+id).destroy();	
											}}).start({'opacity': [1,0]}); 
										} else {
											errorAlert(ERROR, r);
										}
									}	
								}).get();				
				}
		      },
		      {
		        text: NO 
		      }
		    ]
		  })
		});
	return false;		
}
function comment_f_delete(id, username) {
		new StickyWin({
		  content: StickyWin.ui(CONFIRM, DEL_COMMENT.replace('{username}', username), {
		    width: '500px',
		    buttons: [
		      {
		        text: YES,
				onClick: function() {
					new Request({url: 'ajax_checks.php?func=comments_forum_del&id='+id, 
									onComplete: function(r) {
										if(r == 'ok') {
											new Fx.Morph('com_'+id, {duration:800, onComplete: function () { 
												$('com_'+id).destroy();	
											}}).start({'opacity': [1,0]}); 
										} else {
											errorAlert(ERROR, r);
										}
									}	
								}).get();				
				}
		      },
		      {
		        text: NO 
		      }
		    ]
		  })
		});
	return false;		
}
function load_msges(seite, mode) {
	new Request.HTML({
				url: 'ajax_checks.php?func=get_user_messages&mode='+mode+'&page='+seite, 
				useWaiter: true,
				update: $('div_msg'+mode)
	}).get();
	return false;
}
function user_remove_group(id, username, gid, name) {
		new StickyWin({
		  content: StickyWin.ui(CONFIRM, DEL_USER_GROUP.replace('{username}', username).replace('{name}', name), {
		    width: '500px',
		    buttons: [
		      {
		        text: YES,
				onClick: function() {
					new Request({url: 'ajax_checks.php?func=admin&site=delmember&id='+id+'&gid='+gid, 
									onSuccess: function(r) {
										if(r == 'ok') {
											make_info(USER_GROUP_REMOVE.replace('{username}', username).replace('{name}', name), 'accept');
											$('group_'+gid).destroy();
										} else {
											errorAlert(ERROR, r);
										}
									}	
								}).get();				
				}
		      },
		      {
		        text: NO 
		      }
		    ]
		  })
		});
	return false;		
}
function user_add_group(id) {
		group_add_user = new StickyWinFx({
		  draggable: true,
		  fadeDuration: 700,
		  onClose: function () { this.destroy(); }, 
		  content: StickyWin.ui(USER_GROUP_ADD, '<div id="user_add_div"><center><img src="templates/'+DESIGN+'/images/spinner.gif"></center></div>', {
			width: '400px'
		  })
		});
		new Request.HTML({url: 'ajax_checks.php?func=admin&site=user_add_group&id='+id, 
			update: 'user_add_div', 
			evalScripts: true
			}).get();	
}
function user_add_team(id) {
		team_add_user = new StickyWinFx({
		  draggable: true,
		  fadeDuration: 700,
		  onClose: function () { this.destroy(); }, 
		  content: StickyWin.ui(TEAMS_ADD_MEMBER, '<div id="user_add_div"><center><img src="templates/'+DESIGN+'/images/spinner.gif"></center></div>', {
			width: '600px'
		  })
		});
		new Request.HTML({url: 'ajax_checks.php?func=admin&site=user_add_team&id='+id, 
			update: 'user_add_div', 
			evalScripts: true
		}).get();	
}
function user_pw(id) {
		change_pw_win = new StickyWinFx({
		  draggable: true,
		  fadeDuration: 700,
		  onClose: function () { this.destroy(); }, 
		  content: StickyWin.ui(CHANGE_PW, '<div id="change_pw_div"><center><img src="templates/'+DESIGN+'/images/spinner.gif"></center></div>', {
			width: '600px'
		  })
		});
		new Request.HTML({url: 'ajax_checks.php?func=admin&site=user_change_pw&id='+id, 
			update: 'change_pw_div', 
			evalScripts: true
		}).get();	
}
function user_add_g(id, gid, name, username) {
	new Request({url: 'ajax_checks.php?func=admin&site=user_add_group&id='+id+'&gid='+gid, 
			onRequest: function() { $('loader_1').style.visibility = ''; },
			onSuccess: function(r) {
				if(r == 'ok') {
					group_add_user.hide();
					$('gruppen').set('html', $('gruppen').innerHTML + '<div class="comments_menu_link" id="group_'+gid+'" onclick="user_remove_group('+id+', \''+username+'\', '+gid+',  \''+name+'\');"><img src="templates/'+DESIGN+'/images/user_delete.png" alt=""  /> '+name+'</div>');   
				} else {
					errorAlert(ERROR, r);
				}
			}	
		}).get();	
}
function user_ban(id) {
		user_ban_win = new StickyWinFx({
		  draggable: true,
		  fadeDuration: 700,
		  onClose: function () { this.destroy(); }, 
		  content: StickyWin.ui(USER_SET_BAN, '<div id="ban_div"><center><img src="templates/'+DESIGN+'/images/spinner.gif"></center></div>', {
			width: '600px'
		  })
		});
		new Request.HTML({url: 'ajax_checks.php?func=admin&site=user_ban&id='+id, 
			update: 'ban_div', 
			evalScripts: true
		}).get();	
}
function user_delete(id, username) {
		new StickyWin({
		  content: StickyWin.ui(CONFIRM, DEL_USER.replace('{username}', username), {
		    width: '500px',
		    buttons: [
		      {
		        text: YES,
				onClick: function() {
					new Request({url: 'ajax_checks.php?func=admin&site=del_user&id='+id, 
									onSuccess: function(r) {
										if(r == 'ok') {
											make_info(DEL_USER_SUCCESS, 'accept');
										} else {
											errorAlert(ERROR, r);
										}
									}	
								}).get();				
				}
		      },
		      {
		        text: NO 
		      }
		    ]
		  })
		});
	return false;
}
function lotto_ziehungen(anzahl) {
	elemente = $$('#div_ziehung div');
	if(elemente.length < anzahl) {
		anzahl2 = elemente.length;
		dif = anzahl - elemente.length;
		single = elemente[0].innerHTML;
		for(i = 0; i<dif; i++) {
			elm = new Element('div');
			elm.innerHTML = single.replaceAll('_0', '_'+anzahl2++).replace('1.', anzahl2+'.');
			$('div_ziehung').adopt(elm); 
		}
	} else {
		anzahl2 = elemente.length;
		for(i=anzahl2; i>anzahl; i--) {
			elemente[i-1].destroy();
		}
	}
	lotto_vali = new FormValidator('lotto_form');	
}
function check_lottozahlen() {
	zahlen_aktiv = 0;
	$$('#lotto_form input.checkbox').each(function(e) {
		if(e.checked) zahlen_aktiv++;
	});
	if(zahlen_aktiv < 4) {
		$('lotto_submit').disabled = true;
		$$('#lotto_form input.checkbox').each(function(e) {
			e.disabled = false;
		});
	} else if (zahlen_aktiv == 4) {
		$('lotto_submit').disabled = false;
		$$('#lotto_form input.checkbox').each(function(e) {
			if(!e.checked) e.disabled = true;
		});	
	}
}
function lotto_rand() {
	$$('#lotto_form input.checkbox').each(function(e) {
		e.disabled = false;
		e.checked = false;
	});	
	anzahl = 0;
	while(anzahl < 4) {
		zufall = $random(1,24);
		if($('lotto_'+zufall).checked) continue;
		$('lotto_'+zufall).checked = true;
		anzahl++;
	}
	check_lottozahlen();
}
function user_change_rank(id) {
		change_rang = new StickyWinFx({
		  draggable: true,
		  fadeDuration: 700,
		  onClose: function () { this.destroy(); }, 
		  content: StickyWin.ui(CHANGE_RANG, '<div id="change_rang_div"><center><img src="templates/'+DESIGN+'/images/spinner.gif"></center></div>', {
			width: '400px'
		  })
		});
		new Request.HTML({url: 'ajax_checks.php?func=admin&site=change_rank&id='+id, 
			update: 'change_rang_div', 
			evalScripts: true
			}).get();	
}
function load_calendar(el) {
	new Request.HTML({url: el.href+'&ajax=1', useWaiter:true, update: 'calendar_main', onComplete: function(r) {
		new Tips($$('#calendar_main .Tips'), {showDelay: 0});
		$$('#calendar_main .Tips').each(function(e) {
			if (e.getElement('span.tipcontents')) {
				e.store('tip:text', e.getElement('span.tipcontents').get('html'));
			}
		});		
	}}).get();
	return false;
}
function add_quote(username, id, feld) {
	tinyMCE.activeEditor.execCommand('mceInsertContent', false, '[QUOTE='+username+']'+$('quote_'+id).innerHTML+'[/QUOTE]');
	scroll_to(feld, 1000);
}
function load_calendar_mini(j, m) {
	new Request.HTML({url: 'ajax_checks.php?func=calendar&year='+j+'&month='+m, useWaiter:true, update: 'calendar_mini', onComplete: function(r) {new Tips($$('#calendar_mini .Tips'), {showDelay: 0});
		$$('#calendar_mini .Tips').each(function(e) {
			if (e.getElement('span.tipcontents')) {
				e.store('tip:text', e.getElement('span.tipcontents').get('html'));
			}
		});		
	}}).get();
	return false;
}
function code_extend(id) {
	size = $(id).getSize();
	if(size.y < 100) {
		new Fx.Morph(id, {duration:500}).start({'height': [size.y, $(id).getScrollSize().y]});

	} else {
		new Fx.Morph(id, {duration:500}).start({'height': [size.y, 80]});
	}
}
function load_content(el, url) {
	new Request.HTML({useWaiter: true, url: url, update: $(el)}).get();
	return false;
}
function load_year(jahr) {
	web_dates.year = jahr;
	scroll_to('web_jahr', 0);
	$('webyear').reloadData('ajax_checks.php?func=get_webstats&mode=year&year='+jahr);
	$('webmonth').reloadData('ajax_checks.php?func=get_webstats&mode=month&year='+jahr+'&month='+web_dates.month);
	$('webday').reloadData('ajax_checks.php?func=get_webstats&mode=day&year='+jahr+'&month='+web_dates.month+'&day='+web_dates.day);		
}
function load_month(jahr, monat) {
	web_dates.year = jahr;
	web_dates.month = monat;	
	scroll_to('web_monat', 0);
	$('webmonth').reloadData('ajax_checks.php?func=get_webstats&mode=month&year='+jahr+'&month='+web_dates.month);
	$('webday').reloadData('ajax_checks.php?func=get_webstats&mode=day&year='+jahr+'&month='+web_dates.month+'&day='+web_dates.day);		
}
function load_day(jahr, monat, tag) {
	web_dates.year = jahr;
	web_dates.month = monat;	
	web_dates.day = tag;
	scroll_to('web_tag', 0);
	$('webday').reloadData('ajax_checks.php?func=get_webstats&mode=day&year='+jahr+'&month='+web_dates.month+'&day='+web_dates.day);		
}
function load_webstats(art) {
	$('webuser').reloadData('ajax_checks.php?func=get_webstats&mode='+art);	
	msg = '';	
	switch(art) {
		case 'user_hits':
			msg = WEB_STATS_USER;
		break;
		case 'browser_hits':
			msg = BROWSER_HITS;
		break;
		case 'os_hits':
			msg = OS_HITS;		
		break;
		case 'os_visits':
			msg = OS_VISITS;		
		break;
		case 'browser_visits':
			msg = BROWSER_VISITS;		
		break;							
	}
	$('webuser').setParam('labels.label.0.text', '<b>'+msg+'</b>');
	return false;
}
function show_ranks() {
		ranks = new StickyWinFx({
		  draggable: true,
		  fadeDuration: 700,
		  onClose: function () { this.destroy(); }, 
		  content: StickyWin.ui(RANKS, '<div id="ranks"><center><img src="templates/'+DESIGN+'/images/spinner.gif"></center></div>', {
			width: '600px'
		  })
		});
		new Request.HTML({url: 'ajax_checks.php?func=get_ranks', 
			update: 'ranks'
		}).get();	
}  
function get_google_koord(location) {
	var maps_api = new GClientGeocoder();
	koord = "";
    maps_api.setCache(null);
    maps_api.getLatLng(location, function(point) {
       if(point) {
	   	   $('koord').value = point.lat() + "," + point.lng();
       }
    });
}

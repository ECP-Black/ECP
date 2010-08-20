function news_add_link(posi) {
	element = document.createElement('div');
	element.className = 'news_links_input';
	element.appendChild(document.createTextNode('Name:'));
	inputfield = document.createElement('input');
	inputfield.setAttribute('name', 'link');
	inputfield.setAttribute('maxlength', 255);
	inputfield.className = 'smallinput';	
	element.appendChild(inputfield);
	element.appendChild(document.createTextNode(' URL:'));	
	inputfield = document.createElement('input');
	inputfield.setAttribute('name', 'url');
	inputfield.setAttribute('maxlength', 255);
	inputfield.className = 'smallinput';
	element.appendChild(inputfield);
	newlink = document.createElement('a');
	newlink.onclick = new Function('F','news_add_link(this)');
	newimage = document.createElement('img')
	newimage.setAttribute('src', 'templates/'+DESIGN+'/images/link_add.png');
	newlink.appendChild(newimage);
	element.appendChild(newlink);	
	newlink = document.createElement('a');
	newlink.onclick = new Function('F','news_del_link(this)');
	newimage = document.createElement('img')
	newimage.setAttribute('src', 'templates/'+DESIGN+'/images/link_delete.png');
	newlink.appendChild(newimage);
	element.appendChild(newlink);	
	$('links').insertBefore(element, posi.parentNode.nextSibling);
	felder = $('links').getElementsByTagName('div');		
	for(i=0;i<felder.length; i++) {
		felder[i].firstChild.nextSibling.name = 'link_'+i;
		felder[i].firstChild.nextSibling.nextSibling.nextSibling.name = 'url_'+i;
	}
}
function make_options(json, feld) {
	$(feld).empty();
	for(i=0; i<json.length;i++) {
  		el = new Option(json[i].name, json[i].value, false, json[i].selected);
  		$(feld).options[$(feld).length] = el;
		$(feld).options[$(feld).length-1].innerHTML = json[i].name;
	}
}
function news_del_link(linkid) {
	if($('links').getElementsByTagName('div').length > 1) {
	    parentid = linkid.parentNode;
	    $('links').removeChild(parentid);
		$('links').firstChild.removeAttribute('class');	
		felder = $('links').getElementsByTagName('div');		
		for(i=0;i<felder.length; i++) {
			if(i==0) { felder[i].className = '';}			
			felder[i].firstChild.nextSibling.name = 'link_'+i;
			felder[i].firstChild.nextSibling.nextSibling.nextSibling.name = 'url_'+i;
	}			    
	}
}
function news_del(newsid) {
	new StickyWin({
	  content: StickyWin.ui(CONFIRM, DEL_NEWS, {
	    width: '500px',
	    buttons: [
	      {
	        text: YES,
			onClick: function() {
				new Request({url: 'ajax_checks.php?func=admin&site=news&action=del&id='+newsid, 
										 onSuccess: 	function (r) {
										 	 				if(r == 'ok') {
																new Fx.Morph('news_'+newsid, {
																	onComplete: function(){
																		$('news_' + newsid).destroy()
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
function news_pin(id) {
	new Request.HTML({url:'?section=admin&site=news&func=pin&id='+id, update: 'news_table'}).get();
}
function remove_all_rights(field) {
	fields = $(field).getElementsByTagName('input');
	for(i=0; i<fields.length; i++) {
		fields[i].checked = false;
	}
	return false;
}
function getMembers(id) {
	new StickyWinFxModal({
	  draggable: true,
	  fadeDuration: 700,
	  onClose: function () { this.destroy(); }, 
	  content: StickyWin.ui(MEMBERS, '<div id="box_index"><center><img src="templates/'+DESIGN+'/images/spinner.gif"></center></div>', {
		width: '300px'
	  })
	});
	new Request.HTML({url: 'ajax_checks.php?func=admin&site=getmembers&gid='+id, update: 'box_index', method: 'get', evalScripts: true}).get();
	return false;
}
function group_del_member(gid, id) {
		new Request({url: 'ajax_checks.php?func=admin&site=delmember&gid='+gid+'&id='+id, 
								 onSuccess: 	function (r) {
								 	 				if(r == 'ok') {
														new Fx.Morph('member_'+id, {
															onComplete: function(){
																$('member_' + id).destroy()
															}}).start({'opacity': [1,0]});												
													} else {
													   errorAlert(ERROR, r);	
													}
												}
								 }).get();
		return false;		
}
function load_member(gid, page) {
		$('box_index').innerHTML = '<center><img src="templates/'+DESIGN+'/images/spinner.gif"></center>';
		new Request.HTML({url: 'ajax_checks.php?func=admin&site=getmembers&gid='+gid+'&page='+page, 
								update: 'box_index', 
								onSuccess: 
											function() {
												new Autocompleter.Ajax.Json($('username'), 'ajax_checks.php?func=search_member', { postVar: 'username', multiple: true,
													  onRequest: function ()  { $('member_spinner').style.visibility = ''; },
													  onComplete: function ()  { $('member_spinner').style.visibility = 'hidden'; }, 
													  zIndex: 999999 });
												}
	}).get();
		return false;		
}
function add_member(gid) {
		if($('username').value.trim() == '') {
			errorAlert(ERROR, USERNAME_REQUIRED);
		} else {
			$('member_spinner').style.visibility = '';
			new Request({url: 'ajax_checks.php?func=admin&site=addmember&gid='+gid,
									 data: 			'users='+$('username').value,
									 onSuccess: 	function (r) {
									 	 				if(r == 'ok') {
															load_member(gid, 1);														
														} else {
														   errorAlert(ERROR, r);
														   $('member_spinner').style.visibility = 'hidden';	
														}
													}
									 }).post();
		}
		return false;
}
function group_del(id) {
	new StickyWin({
	  content: StickyWin.ui(CONFIRM, DEL_GROUP, {
	    width: '500px',
	    buttons: [
	      {
	        text: YES,
			onClick: function() {
				new Request({url: 'ajax_checks.php?func=admin&site=delgroup&gid='+id, 
										 onSuccess: 	function (r) {
										 	 				if(r == 'ok') {
																new Fx.Morph('group_'+id, {
																	onComplete: function(){
																		$('group_'+id).destroy();
																		check_color('groups_table', 1);																		
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
function download_browse() {
	if(typeof(dl_browse) === 'undefined') {
		dl_browse = new StickyWinFxModal({
		  draggable: true,
		  fadeDuration: 700,
		  dragOptions: { 
			  onComplete: function(e){
			  	swiffy.reposition();
			  } 
		  },
		  content: StickyWin.ui(BROWSE, '<div id="browse_content"><center><img src="templates/'+DESIGN+'/images/spinner.gif"></center></div>', {
			width: '600px'
		  })
		});
		new Request.HTML({url: 'ajax_checks.php?func=admin&site=getdlordner',
			update: 'browse_content', 
			evalScripts: true	
			}).get();		
	} else {
		dl_browse.show();			
	}
	return false;		
}
function load_dir(folder) {
	$('browse_content').innerHTML = '<center><img src="templates/'+DESIGN+'/images/spinner.gif"></center>';
	new Request.HTML({url:'ajax_checks.php?func=admin&site=getdlordner', 
		update: 'browse_content', 
		data: 'dir='+folder,
		method: 'post',
		evalScripts: true			
		}).post();
	return false;
}
function set_download(url, size) {
	$('url').value = url.replace('////','/');
	$('size').value = size.toInt();
	$('modifkator').options[0].selected = true;
	dl_browse.hide();
	$('modalOverlay').hide();
	dl_form.validateField($('url'));
	dl_form.validateField($('size'));
}
function dl_del_file(ordner, datei) {
	new StickyWin({
	  content: StickyWin.ui(CONFIRM, DEL_FILE, {
	    width: '500px',
	    buttons: [
	      {
	        text: YES,
			onClick: function() {
				new Request({url: 'ajax_checks.php?func=admin&site=delfile', data: 'dir='+ordner+'/&name='+datei,
										 onSuccess: 	function (r) {
										 	 				if(r == 'ok') {
																load_dir(ordner);										
															} else {
															   errorAlert(ERROR, r);	
															}
														}
										 }).post();				
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
function add_folder(dir) {
	if($('add_folder').value.trim() == '') {
		errorAlert(ERROR, DIR_REQUIRED);
	} else if (/[^a-zA-Z0-9_\-]/.test($('add_folder').get("inputValue"))) {
		errorAlert(ERROR, INVALID_FOLDERNAME);
	} else {
		new Request({
			url: 'ajax_checks.php?func=admin&site=createfolder', 
			data: 'dir='+dir+'&name='+$('add_folder').get("inputValue"),
			onSuccess: function (r) {
				if(r == 'ok') {
					load_dir(dir);
				} else {
					errorAlert(ERROR, r);
				}
			}			
			}).post();
	}
}
function load_dl()  {
	$('dl_loader').style.visibility = '';
	id = $('all_downloads').options[$('all_downloads').selectedIndex].value;
	if(id == 0) {
		$('download_form').reset();
		$('download_form').action = '?section=admin&site=downloads&func=add';
		$('dl_submit').value = ADD;	
		$('dl_loader').style.visibility = 'hidden';	
	} else {
		new Request.JSON({url: "ajax_checks.php?func=admin&site=getdl&id="+id, 
			onComplete: function(data) {
				if(typeof(data.error) === 'undefined') {
					$('download_form').action = '?section=admin&site=downloads&func=editdl&id='+id;
					$('dl_submit').value = EDIT;
				    $('name').value = data.name;
				    $('url').value = data.url;
					$('modifkator').options[0].selected = true;
				    $('homepage').value = data.homepage;
				    $('version').value = data.version;						
				    $('size').value = data.size;
				    $('downloads').value = data.downloads;	
					for(i=0;i<$('cID').options.length;i++) {
						($('cID').options[i].value == data.cID) ? $('cID').options[i].selected = true : $('cID').options[i].selected = false;
					}
					if(data.access == '') {
						$$('#rights_dl option').each(function (e) { 
							if(e.value == 'all') 
								e.selected = true; 
							else 
								e.selected = false; 
						});
					} else {
						ids = data.access.split(',');
						$$('#rights_dl option').each(function (e) { 
							if(ids.contains(e.value)) 
								e.selected = true; 
							else 
								e.selected = false; 
						});
					}
					$$('#download_form textarea').each( function (e) { 
						lang = e.name.substr(15);
						el = tinyMCE.get(e.name);
						if(typeof(data['info'][lang]) === 'undefined') {
							e.value = '';
							el.setContent(''); 
						} else {
							e.value = data['info'][lang];						
							el.setContent(data['info'][lang]);								
						}			
					});							
					$('dl_loader').style.visibility = 'hidden';
				} else {
					errorAlert(ERROR, data.error);
				}
		}}).get();
	}
}
function delete_dl() {
	id = $('all_downloads').options[$('all_downloads').selectedIndex].value;
	name = $('all_downloads').options[$('all_downloads').selectedIndex].text;
	if(id != 0) {
		new StickyWin({
		  content: StickyWin.ui(CONFIRM, DEL_DL.replaceAll('{name}', name), {
		    width: '500px',
		    buttons: [
		      {
		        text: YES,
				onClick: function() {
					new Request({url: 'ajax_checks.php?func=admin&site=del_dl&id='+id,
											 onSuccess: 	function (r) {
											 	 				if(r == 'ok') {
																	$('all_downloads').options[$('all_downloads').selectedIndex].destroy();
																	$('all_downloads').options[0].selected = true;
																	make_info(DEL_DL_SUCCESS, 'accept');										
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
function load_kate()  {
	$('kate_loader').style.visibility = '';
	id = $('all_kate').options[$('all_kate').selectedIndex].value;
	if(id == 0) {
		$('dl_kate_loader').style.visibility = 'hidden';
		$('kate_submit').disabled = false;			
		$('dl_kate').reset();	
		$('dl_kate').action = '?section=admin&site=downloads&func=addkate';
		$('kate_submit').value = ADD;	
	} else {
		new Request.JSON({url: "ajax_checks.php?func=admin&site=getkate&id="+id, 
			onComplete: function(data) {
				if(typeof(data.error) === 'undefined') {
					$('dl_kate').action = '?section=admin&site=downloads&func=editkate&id='+id;
					$('kate_submit').value = EDIT;
				    $('kname').value = data.kname;
					for(i=0;i<$('subID').options.length;i++) {
						($('subID').options[i].value == data.subkID) ? $('subID').options[i].selected = true : $('subID').options[i].selected = false;
					}
					if(data.access == '') {
						$$('#rights option').each(function (e) { 
							if(e.value == 'all') 
								e.selected = true; 
							else 
								e.selected = false; 
						});
					} else {
						ids = data.access.split(',');
						$$('#rights option').each(function (e) { 
							if(ids.contains(e.value)) 
								e.selected = true; 
							else 
								e.selected = false; 
						});
					}
					$$('#dl_kate textarea').each( function (e) { 
						lang = e.name.substr(12);
						el = tinyMCE.get(e.name);
						if(typeof(data['beschreibung'][lang]) === 'undefined') {
							e.value = '';
							el.setContent(''); 
						} else {
							e.value = data['beschreibung'][lang];						
							el.setContent(data['beschreibung'][lang]);								
						}			
					});									
					$('kate_loader').style.visibility = 'hidden';
				} else {
					errorAlert(ERROR, data.error);
				}
		}}).get();
	}
}
function delete_kate() {
	id = $('all_kate').options[$('all_kate').selectedIndex].value;
	name = $('all_kate').options[$('all_kate').selectedIndex].text;
	if(id != 0) {
		new StickyWin({
		  content: StickyWin.ui(CONFIRM, DEL_DL_KATE.replaceAll('{name}', name), {
		  width: '500px',
		  buttons: [{	
		  		text: YES,
				onClick: function() {
					new Request({url: '?section=admin&site=downloads&func=delkate&id='+id, 
							onRequest: 		function () {	$('kate_loader').style.visibility = '';
															$('dl_loader').style.visibility = '';	
											},
							onSuccess: 	function (r) {
												if(r == 'ok') {
														make_info(DEL_KATE_SUCCESS, 'accept');
														new Request({url: '?section=admin&site=downloads&func=getkates', 
															onSuccess: 	function (r) {
																rep = JSON.decode(r); 
																make_options(rep, 'subID'); 
																make_options(rep, 'all_kate'); 
																make_options(rep, 'cID');																
																$('kate_loader').style.visibility = 'hidden';																												
															}
															}).get();	
														new Request({url: '?section=admin&site=downloads&func=getdls', 
															onSuccess: 	function (r) {
																rep = JSON.decode(r); make_options(rep, 'all_downloads');
																$('dl_loader').style.visibility = 'hidden';																	
															}
														}).get();																								 								
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
function team_upload() {
	if(typeof(upload) === 'undefined') {
		upload = new StickyWinFxModal({
		  draggable: true,
		  fadeDuration: 700,
		  dragOptions: { 
			  onComplete: function(e){
			  	swiffy.reposition();
			  } 
		  },		  
		  onClose: function () { },
		  content: StickyWin.ui(UPLOAD, '<div id="upload"></div>', {
			width: '600px'
		  })
		});
		var myAjax = new Request.HTML({url: 'ajax_checks.php?func=admin&site=team_upload_form',
			update: 'upload', 
			method: 'get',
			evalScripts: true	
			}).get();		
	} else {
		upload.show();			
	}
	return false;		
}
function teams_add_user(id) {
	if(id != 0) {
		win_teams_add_user = new StickyWinFxModal({
		  draggable: true,
		  fadeDuration: 700,
		  onClose: function () { this.destroy(); },
		  content: StickyWin.ui(TEAMS_ADD_MEMBER, '<div id="teams_add_div"><center><img src="templates/'+DESIGN+'/images/spinner.gif"></center></div>', {
			width: '600px'
		  })
		});
		new Request.HTML({url: 'ajax_checks.php?func=admin&site=team_add_member&id='+id, 
			update: 'teams_add_div', 
			evalScripts: true
		}).get();	
	}		
}
function update_admin_teams() {
		var myAjax = new Request.HTML({url: '?section=admin&site=teams&func=get_teams&ajax=1', 
			update: 'teams_overview', 
			onRequest: function () { $('teams_overview').set('html', '<center><img src="templates/'+DESIGN+'/images/spinner.gif"></center>'); } ,
			evalScripts: true	
			}).get();		
}
function member_switch_status(gid, uid)  {
		new Request.HTML({url: '?section=admin&site=teams&func=switch_status&gid='+gid+'&uid='+uid, 
			update: 'teams_overview', 
			evalScripts: true	
			}).get();		
}
function team_del_member(gid, uid)  {
		new Request({url: '?section=admin&site=teams&func=delmember&gid='+gid+'&uid='+uid, 
			onSuccess: function(r) {
				if(r == 'ok') {
					new Fx.Slide(gid+'_'+uid).slideOut().chain(function () { 
						$(gid+'_'+uid).destroy();
						check_color('team_'+gid);	
					}); 											
				} else {
					errorAlert(ERROR, r);
				}
			}	
			}).get();		
}
function teams_edit_member(gid, uid) {
		win_teams_edit_user = new StickyWinFxModal({
		  draggable: true,
		  fadeDuration: 700,
		  onClose: function () { this.destroy(); },
		  content: StickyWin.ui(TEAMS_EDIT_MEMBER, '<div id="teams_edit"><center><img src="templates/'+DESIGN+'/images/spinner.gif"></center></div>', {
			width: '600px'
		  })
		});
		new Request.HTML({url: 'ajax_checks.php?func=admin&site=team_edit_member&id='+gid+'&uid='+uid, 
			update: 'teams_edit', 
			evalScripts: true	
		}).get();		
}
function teams_del(id, name) {
	new StickyWin({
	  content: StickyWin.ui(CONFIRM, DEL_TEAM.replaceAll('{team}', name), {
	    width: '500px',
	    buttons: [
	      {
	        text: YES,
			onClick: function() {
				new Request({url: '?section=admin&site=teams&func=del&id='+id, 
										 onSuccess: 	function (r) {
										 	 				if(r == 'ok') {
																update_admin_teams();										
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
function teams_edit(id) {
	new Fx.Scroll(window, {transition: Fx.Transitions.Elastic.easeOut, duration: 1500}).toElement('team_form');
	new Request.JSON({url: 'ajax_checks.php?func=admin&site=get_team&id='+id, 
		onSuccess: function(data) {
			$('loader').style.visibility = 'hidden';
			if(typeof(data.error) === 'undefined') {
				$('team_form').action = '?section=admin&site=teams&func=edit&id='+id;
				$('team_submit').value = EDIT;
				$('name').value = data.tname;
				(data.cw == 1) ? $('cw').checked = true : $('cw').checked = false; 			
				(data.joinus == 1) ? $('joinus').checked = true : $('joinus').checked = false; 		
				(data.fightus == 1) ? $('fightus').checked = true : $('fightus').checked = false;
				$$('#tpic option').each(function (e) { 
					if(e.value == data.tpic) 
						e.selected = true; 
					else 
						e.selected = false; 
				});
				$$('#grID option').each(function (e) { 
					if(e.value == data.grID) 
						e.selected = true; 
					else 
						e.selected = false; 
				});														
				$$('#team_form textarea').each( function (e) { 
					lang = e.name.substr(12);
					el = tinyMCE.get(e.name);
					if(typeof(data['info'][lang]) === 'undefined') {
						e.value = '';
						el.setContent(''); 
					} else {
						e.value = data['info'][lang];						
						el.setContent(data['info'][lang]);								
					}			
				});	
			} else {
				errorAlert(ERROR, data.error);
			}
		},
		onRequest: function() {
			$('loader').style.visibility = '';
		}
	}).get();
}
function games_edit(id) {
		$('games_spinner').style.visibility = '';
		games_form_val.reset();
		new Fx.Scroll(window, {transition: Fx.Transitions.Elastic.easeOut, duration: 1500}).toElement('games_form');
		new Request.JSON({url: "ajax_checks.php?func=admin&site=edit_games&id="+id, 
			onSuccess: function(data) {
				if(typeof(data.error) === 'undefined') {
					$('games_form').action = '?section=admin&site=games&func=edit&id='+id;
					$('games_submit').value = EDIT;
				    $('name').value = data.gamename;
					$('short').value = data.gameshort;
					$('game_icon').set('html', '<img src="images/games/'+data.icon+'" alt="" title="'+data.gamename+'" />');
					(data.fightus == 1) ? $('fightus').checked = true : $('fightus').checked = false;
					for(i=0;i<$('icon').options.length;i++) {
						($('icon').options[i].value == data.icon) ? $('icon').options[i].selected = true : $('icon').options[i].selected = false;
					}			
					$('games_spinner').style.visibility = 'hidden';
				} else {
					errorAlert(ERROR, data.error);
				}
		}}).get();	
}
function map_edit(id) {
		$('maps_spinner').style.visibility = '';
		maps_form_val.reset();
		new Fx.Scroll(window, {transition: Fx.Transitions.Elastic.easeOut, duration: 1500}).toElement('maps_form');
		new Request.JSON({url: "ajax_checks.php?func=admin&site=edit_map&id="+id, 
			onSuccess: function(data) {
				if(typeof(data.error) === 'undefined') {
					$('maps_form').action = '?section=admin&site=games&func=editmap&id='+id;
					$('maps_submit').value = EDIT;
				    $('name').value = data.locationname;
					for(i=0;i<$('gameid').options.length;i++) {
						($('gameid').options[i].value == data.gID) ? $('gameid').options[i].selected = true : $('gameid').options[i].selected = false;
					}			
					$('maps_spinner').style.visibility = 'hidden';
				} else {
					errorAlert(ERROR, data.error);
				}
		}}).get();	
}
function matchtype_edit(id) {
		$('matchtype_spinner').style.visibility = '';
		matchtype_form_val.reset();
		new Fx.Scroll(window, {transition: Fx.Transitions.Elastic.easeOut, duration: 1500}).toElement('matchtype_form');
		new Request.JSON({url: "ajax_checks.php?func=admin&site=edit_matchtype&id="+id, 
			onSuccess: function(data) {
				if(typeof(data.error) === 'undefined') {
					$('matchtype_form').action = '?section=admin&site=matchtype&func=edit&id='+id;
					$('matchtype_submit').value = EDIT;
				    $('name').value = data.matchtypename;	
					(data.fightus == 1) ? $('fightus').checked = true : $('fightus').checked = false;	
					$('matchtype_spinner').style.visibility = 'hidden';
				} else {
					errorAlert(ERROR, data.error);
				}
		}}).get();	
}
function game_changed(el) {
	if(el.selectedIndex) {
		new Request({url: 'ajax_checks.php?func=admin&site=get_war_maps&id=' + el.options[el.selectedIndex].value, 
			onRequest: 		function() {$('games_spinner').style.visibility = '';},
			onSuccess: 	function(r) {
								respon = JSON.decode(r);
								$$('#fieldset_maps select').each( function(e) { make_options(respon, e) });
								cw_form_val.reset();
								$('games_spinner').style.visibility = 'hidden';
							}
		}).get();	
	}
}
function update_cw_form() {
	divs = $$('div.clanwar_map');
	i = 1;
	divs.each(function (e) { e.id = 'div_map_'+i++; });
	for(i=1;i<=divs.length;i++) {
		divs[i-1].getElementsByTagName('label')[0].set('text', CW_MAP + ' ' + i);
		divs[i-1].getElementsByTagName('select')[0].id = 'map_'+i;
		divs[i-1].getElementsByTagName('select')[0].name = 'map_'+i;		
		divs[i-1].getElementsByTagName('input')[0].name = 'score_'+i+'_own';	
		divs[i-1].getElementsByTagName('input')[1].name = 'score_'+i+'_opp';
		divs[i-1].getElementsByTagName('input')[0].id = 'score_'+i+'_own';	
		divs[i-1].getElementsByTagName('input')[1].id = 'score_'+i+'_opp';
		divs[i-1].getElementsByTagName('img')[0].onclick = new Function('F','clanwar_add_map('+i+')');	
		divs[i-1].getElementsByTagName('img')[1].onclick = new Function('F','clanwar_del_map('+i+')');	
	}
	cw_form_val = new FormValidator($('clanwar_form'));
}
function clanwar_add_map(e) {
	cw_form_val.reset();
	$('div_map_' + e).clone().injectAfter('div_map_' + e);
	update_cw_form();

}
function clanwar_del_map(e) {
	if ($$('div.clanwar_map').length > 1) {
		new Fx.Slide('div_map_' + e).slideOut().chain(function(){
			$('div_map_' + e).destroy();
			update_cw_form();
		});
	}
}
function load_opp(id) {
	if(id == 0) {
		$('oppname').value = '';
		$('oppshort').value = '';
		$('homepage').value = '';
		$$('#country option').each(function (e) {
			(e.value == 'de') ? e.selected = true : e.selected = false;
		});		
		set_flagge($('country'));
		cw_form_val.reset();
	} else {
		new Request.JSON({url: "ajax_checks.php?func=admin&site=get_opp&id="+id, 
			onRequest: function () { $('opp_spinner').setStyle('opacity', 1); }, 
			onSuccess: function(data) {
				if(typeof(data.error) === 'undefined') {
				    $('oppname').value = data.oppname;	
					$('oppshort').value = data.oppshort;	
					$('homepage').value = data.homepage;	
					$$('#country option').each(function (e) {
						(e.value == data.country) ? e.selected = true : e.selected = false;
					});
					set_flagge($('country'));
					$('opp_spinner').setStyle('opacity', 0);
					cw_form_val.reset();
				} else {
					errorAlert(ERROR, data.error);
				}
		}}).get();	
	}
}
function cw_edit_opp(id) {
		win_opp_edit = new StickyWinFxModal({
		  draggable: true,
		  fadeDuration: 700,
		  onClose: function () { this.destroy(); },
		  content: StickyWin.ui(OPP_EDIT, '<div id="opp_edit_div"><center><img src="templates/'+DESIGN+'/images/spinner.gif"></center></div>', {
			width: '600px'
		  })
		});
		new Request.HTML({url: 'ajax_checks.php?func=admin&site=edit_opp&id='+id, 
			update: 'opp_edit_div', 
			evalScripts: true	
		}).get();	
	return false;	
}
function cw_upload(id) {
		win_screen_upload = new StickyWinFxModal({
		  draggable: true,
		  fadeDuration: 700,
		  dragOptions: { 
			  onComplete: function(e){
			  	uplooad.each(function(e) { e.reposition(); });
			  } 
		  },		  
		  onClose: function () { this.destroy(); },
		  content: StickyWin.ui(SCREEN_UPLOAD, '<div id="screen_upload"><center><img src="templates/'+DESIGN+'/images/spinner.gif"></center></div>', {
			width: '600px'
		  })
		});
		new Request.HTML({
			url: 'ajax_checks.php?func=admin&site=cw_upload_screens&id='+id, 
			update: 'screen_upload', 
			evalScripts: true	
		}).get();	
	return false;	
}
function cw_screen_del(id, sid)  {
		new Request({url: 'ajax_checks.php?func=admin&site=cw_screen_delete&id='+id,
			onSuccess: function(r) {
				if(r == 'ok') {
					new Fx.Morph('file_'+id, {duration:800, onComplete: function () { 
						$('file_'+id).destroy();
						check_color('score_'+sid, 1);
					}}).start({'opacity':[1,0]}); 											
				} else {
					errorAlert(ERROR, r);
				}
			}	
			}).get();		
}
function load_cws(seite) {
	new Request.HTML({url: 'ajax_checks.php?func=admin&site=get_wars&page='+seite, 
		update: 'wars',
		useWaiter: true 
	}).get();
	return false;
}
function clanwar_del(id, datum)  {
	if(id != 0) {
		new StickyWin({
		  content: StickyWin.ui(CONFIRM, CLANWAR_DELETE.replaceAll('{datum}', datum), {
		    width: '500px',
		    buttons: [
		      {
		        text: YES,
				onClick: function() {
					new Request({url: 'ajax_checks.php?func=admin&site=clanwar_delete&id='+id, 
								onComplete: function(r) {
									if(r == 'ok') {
										new Fx.Morph('cw_'+id, {duration:800, onComplete: function(){
											$('cw_' + id).destroy();
											check_color('clanwars_overview', 1);
											check_color('nextwars_overview', 1);
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
}
function games_del(id, name)  {
	if(id != 0) {
		new StickyWin({
		  content: StickyWin.ui(CONFIRM, GAME_DELETE.replaceAll('{name}', name), {
		    width: '500px',
		    buttons: [
		      {
		        text: YES,
				onClick: function() {
					new Request({
						url: 'ajax_checks.php?func=admin&site=game_delete&id='+id, 
						onSuccess: function(r) {
							if(r == 'ok') {
								new Fx.Morph('game_' + id, {
									duration: 800,
									onComplete: function(){
										$('game_' + id).destroy();
										check_color('games_table', 1);
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
function map_del(id, name)  {
	if(id != 0) {
		new StickyWin({
		  content: StickyWin.ui(CONFIRM, MAP_DELETE.replaceAll('{name}', name), {
		    width: '500px',
		    buttons: [
		      {
		        text: YES,
				onClick: function() {
					new Request({
						url: 'ajax_checks.php?func=admin&site=map_delete&id='+id,
						onSuccess: function(r) {
							if(r == 'ok') {
								new Fx.Morph('map_' + id, {
									duration: 800,
									onComplete: function(){
										$('map_' + id).destroy();
										check_color('maps_table', 1);
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
function matchtype_del(id, name)  {
	if(id != 0) {
		new StickyWin({
		  content: StickyWin.ui(CONFIRM, MATCHTYPE_DELETE.replaceAll('{name}', name), {
		    width: '500px',
		    buttons: [
		      {
		        text: YES,
				onClick: function() {
					new Request({url: 'ajax_checks.php?func=admin&site=matchtype_delete&id='+id, 
								onSuccess: function(r) {
									if(r == 'ok') {
										new Fx.Morph('matchtype_' + id, {
											duration: 800,
											onComplete: function(){
												$('matchtype_' + id).destroy();
												check_color('matchtype_table', 1);
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
function topic_edit(id) {
	new Fx.Scroll(window, {transition: Fx.Transitions.Elastic.easeOut, duration: 1500}).toElement('topic_form');
	new Request.JSON({url: 'ajax_checks.php?func=admin&site=get_topic&id='+id, 
		onSuccess: function(data) {
			$('topic_submit_spinner').style.visibility = 'hidden';
			if(typeof(data.error) === 'undefined') {
				$('topic_form').action = '?section=admin&site=topics&func=edit&id='+id;
				$('submit_topic').value = EDIT;
				$('topicname').value = data.topicname;
				$('beschreibung').value = data.beschreibung;				
				$$('#topicbild option').each(function (e) { 
					if(e.value == data.topicbild) 
						e.selected = true; 
					else 
						e.selected = false; 
				});
			} else {
				errorAlert(ERROR, data.error);
			}
		},
		onRequest: function() {
			$('topic_submit_spinner').style.visibility = '';
		}
	}).get();
}
function topic_del(id, name)  {
	if(id != 0) {
		new StickyWin({
		  content: StickyWin.ui(CONFIRM, DEL_TOPIC.replaceAll('{name}', name), {
		    width: '500px',
		    buttons: [
		      {
		        text: YES,
				onClick: function() {
					new Request({url: '?section=admin&site=topics&func=del&id='+id,
								onSuccess: function(r) {
									if(r == 'ok') {
										new Fx.Morph('topic_' + id, {
											duration: 800,
											onComplete: function(){
												$('topic_' + id).destroy();
												check_color('topic_table', 1);
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
function award_edit(id) {
	new Fx.Scroll(window, {transition: Fx.Transitions.Elastic.easeOut, duration: 1500}).toElement('award_form');
	new Request.JSON({
		url: 'ajax_checks.php?func=admin&site=get_award&id='+id,
		onSuccess: function(data) {
			$('award_submit_spinner').style.visibility = 'hidden';
			if(typeof(data.error) === 'undefined') {
				$('award_form').action = '?section=admin&site=awards&func=edit&id='+id;
				$('submit_award').value = EDIT;
				$('eventname').value = data.eventname;
				$('eventdatum').value = data.eventdatum;
				$('spieler').value = data.spieler;
				$('url').value = data.url;					
				$('preis').value = data.preis;
				$$('#platz option').each(function (e) { 
					if(e.value == data.platz) 
						e.selected = true; 
					else 
						e.selected = false; 
				});
				$$('#gID option').each(function (e) { 
					if(e.value == data.gID) 
						e.selected = true; 
					else 
						e.selected = false; 
				});
				$$('#teamID option').each(function (e) { 
					if(e.value == data.teamID) 
						e.selected = true; 
					else 
						e.selected = false; 
				});	
				$$('#award_form textarea').each( function (e) { 
					lang = e.name.substr(12);
					el = tinyMCE.get(e.name);
					if(typeof(data['bericht'][lang]) === 'undefined') {
						e.value = '';
						el.setContent(''); 
					} else {
						e.value = data['bericht'][lang];						
						el.setContent(data['bericht'][lang]);								
					}			
				});																
			} else {
				errorAlert(ERROR, data.error);
			}
		},
		onRequest: function() {
			$('award_submit_spinner').style.visibility = '';
		}
	}).get();
}
function award_del(id, name)  {
	if(id != 0) {
		new StickyWin({
		  content: StickyWin.ui(CONFIRM, DEL_AWARD.replaceAll('{name}', name), {
		    width: '500px',
		    buttons: [
		      {
		        text: YES,
				onClick: function() {
					new Request({ url: '?section=admin&site=awards&func=del&id='+id, 
								onSuccess: function(r) {
									if(r == 'ok') {
										var morph = new Fx.Morph('award_'+id, {duration:800, onComplete: function(){
											$('award_' + id).destroy();
											check_color('award_table', 1);
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
}
function fightus_view(id) {
	new Request({url: 'ajax_checks.php?func=admin&site=fightus_view&id='+id, 
			onRequest: function() { 
				$('view_fightus').set('html', '<center><img src="templates/'+DESIGN+'/images/spinner.gif"></center>');
			},
			onSuccess: function(r) {
				$('view_fightus').set('html', r);
				scroll_to('view_fightus', 800);
			}
		}).get();
}
function fightus_finished(id) {
	new Request({url: 'ajax_checks.php?func=admin&site=fightus_finish&id='+id, 
			onSuccess: function(r) {
				$('finish_from').set('html', FINISHED_FROM + r);
				$('finished_by_'+id).set('html', r);
			}
		}).get();
}
function fightus_del(id, name)  {
	if(id != 0) {
		new StickyWin({
		  content: StickyWin.ui(CONFIRM, DEL_FIGHTUS.replaceAll('{name}', name), {
		    width: '500px',
		    buttons: [
		      {
		        text: YES,
				onClick: function() {
					new Request({url: '?section=admin&site=fightus&func=del&id='+id, 
								onSuccess: function(r) {
									if(r == 'ok') {
										new Fx.Morph('fightus_'+id, {duration:800, onComplete: function(){
											$('fightus_' + id).destroy();
											check_color('fightus_table', 1);
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
}
function joinus_view(id) {
	new Request({url: 'ajax_checks.php?func=admin&site=joinus_view&id='+id, 
			onRequest: function() { 
				$('view_joinus').set('html', '<center><img src="templates/'+DESIGN+'/images/spinner.gif"></center>');
			},
			onSuccess: function(r) {
				$('view_joinus').set('html', r);
				scroll_to('view_joinus', 800);
			}
		}).get();
}
function joinus_finished(id) {
	new Request({url: 'ajax_checks.php?func=admin&site=joinus_finish&id='+id, 
			onSuccess: function(r) {
				$('finish_from').set('html', FINISHED_FROM + r);
				$('finished_by_'+id).set('html', r);
			}
		}).get();
}
function joinus_del(id, name)  {
	if(id != 0) {
		new StickyWin({
		  content: StickyWin.ui(CONFIRM, DEL_JOINUS.replaceAll('{name}', name), {
		    width: '500px',
		    buttons: [
		      {
		        text: YES,
				onClick: function() {
					new Request({url: '?section=admin&site=joinus&func=del&id='+id, 
								onSuccess: function(r) {
									if(r == 'ok') {
										new Fx.Morph('joinus_' + id, {
											duration: 800,
											onComplete: function(){
												$('joinus_' + id).destroy();
												check_color('joinus_table', 1);
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
function reset_survey_form() {
	sur_form_val.reset();
	$('frage').value = '';
	$('start').value = '';	
	$('ende').value = '';	
	$('sperre').value = '';
	$('antworten').value = 1;
	$('survey_form').action = '?section=admin&site=survey&func=add';
	$('survey_submit').value = ADD;	
	$('div_antworten').set('html', '<div class="answer"><label for="answer_1">'+ANSWER+' 1:</label><input type="text" maxlength="100" id="answer_1" name="answer_1" class="required" /> <img src="templates/'+DESIGN+'/images/survey_add.png" alt="" title="'+SURVEY_ANSWER_ADD+'" style="cursor:pointer" onClick="survey_add_answer(this)" /> <img src="templates/'+DESIGN+'/images/survey_delete.png" alt="" title="'+SURVEY_ANSWER_DELETE+'" style="cursor:pointer" onClick="survey_del_answer(this)" /></div></div>');
	$('multi').options[0].selected = true;
	$$('#rights option').each(function (e) { 
		if(e.value == 'all') 
			e.selected = true; 
		else 
			e.selected = false; 
	});	;
	felder = $$('div .answer');
	if (felder.length > 1) {
		felder.each(function(e) {
			if (typeof(erstes) === 'undefined') {
				erstes = 1;
			} else {
				slide = new Fx.Slide(e);
				slide.slideOut();
			}
		});
		delete erstes;
	}
}
function survey_close(id) {
	if(id != 0) {
		new StickyWin({
		  content: StickyWin.ui(CONFIRM, CLOSE_SURVEY, {
		    width: '500px',
		    buttons: [
		      {
		        text: YES,
				onClick: function() {
					new Request({url: 'ajax_checks.php?func=admin&site=closesurvey&id='+id,
								onSuccess: function(res) {
									if(res.clean() == 'ok') {
										new Request({url: 'ajax_checks.php?func=admin&site=getsurveys', 
											onRequest: function(){
												$('survey_div').set('html', '<center><img src="templates/' + DESIGN + '/images/spinner.gif"></center>');
											},
											onSuccess: function(r){
												$('survey_div').set('html', r);
											}
										}).get();											
									} else {
										errorAlert(ERROR, res);
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
function survey_delete(id)  {
	if(id != 0) {
		new StickyWin({
		  content: StickyWin.ui(CONFIRM, DEL_SURVEY, {
		    width: '500px',
		    buttons: [
		      {
		        text: YES,
				onClick: function() {
					new Request({url: 'ajax_checks.php?func=admin&site=survey_delete&id='+id,
								onSuccess: function(r) {
									if(r == 'ok') {
										new Fx.Morph('survey_' + id, {
											duration: 800,
											onComplete: function(){
												$('survey_' + id).destroy();
												check_color('survey_overview', 1);
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
function survey_edit(id) {
	reset_survey_form();
	new Fx.Scroll(window, {transition: Fx.Transitions.Elastic.easeOut, duration: 1500}).toElement('survey_form');
	new Request.JSON({url: 'ajax_checks.php?func=admin&site=get_survey&id='+id, 
		onSuccess: function(data) {
			$('survey_loader').style.visibility = 'hidden';
			if(typeof(data.error) === 'undefined') {
				$('survey_form').action = '?section=admin&site=survey&func=edit&id='+id;
				$('survey_submit').value = EDIT;
				$('frage').value = data.frage;
				$('start').value = data.start;
				$('ende').value = data.ende;
				$('antworten').value = data.antworten;
				$('sperre').value = (data.sperre/60).toInt();							
				if(data.access == '') {
					$$('#rights option').each(function (e) { 
						if(e.value == 'all') 
							e.selected = true; 
						else 
							e.selected = false; 
					});
				} else {
					ids = data.access.split(',');
					$$('#rights option').each(function (e) { 
						if(ids.contains(e.value)) 
							e.selected = true; 
						else 
							e.selected = false; 
					});
				}
				i = 1;
				antworten = '';
				data.answers.each(function(e) {
					antworten += '<div class="answer"><label for="answer_old_'+e.answerID+'">'+ANSWER+' '+i+':</label><input type="text" maxlength="100" id="answer_old_'+e.answerID+'" value="'+e.answer+'" name="answer_old_'+e.answerID+'" class="required smallinput" /> <input type="text" maxlength="7" id="votes_'+e.answerID+'" value="'+e.votes+'" name="votes_'+e.answerID+'" class="required validate-number" style="width: 4em;" /> <img src="templates/'+DESIGN+'/images/survey_add.png" alt="" title="'+SURVEY_ANSWER_ADD+'" style="cursor:pointer" onClick="survey_add_answer(this)" /> <img src="templates/'+DESIGN+'/images/survey_delete.png" alt="" title="'+SURVEY_ANSWER_DELETE+'" style="cursor:pointer" onClick="survey_del_answer1(this, '+ e.answerID+', '+id+');" /></div></div>';			
					i++;
				});
				sur_form_val = new FormValidator($('survey_form')); 
				$('div_antworten').set('html', antworten);		
			} else {
				errorAlert(ERROR, data.error);
			}
		},
		onRequest: function() {
			$('survey_loader').style.visibility = '';
		}
	}).get();
}
function survey_del_answer1(feld, answer, sid) {
	if ($$('div .answer').length > 1) {
			new StickyWin({
				content: StickyWin.ui(CONFIRM, DEL_SURVEY_ANSWER, {
					width: '500px',
					buttons: [{
						text: YES,
						onClick: function(){
							new Request({url: 'ajax_checks.php?func=admin&site=survey_answer_delete&id=' + answer + '&surid='+sid, 
								onSuccess: function(r){
									if (r == 'ok') {
										slide = new Fx.Slide(feld.parentNode);
										slide.slideOut().chain(function(e){
											feld.parentNode.destroy();
											nr = 1;
											$$('div .answer').each(function(elm){
												elm.getFirst().set('text', elm.getFirst().getText().replace(/\d+/, nr++));			
											});
										});
									}
									else {
										errorAlert(ERROR, r);
									}
								}
							}).get();
						}
					}, {
						text: NO
					}]
				})
			});
			return false;
	}	
}
function check_forum_type(el) {
	if(selected_value(el) == 0) {
		$$('div .isforum').each(function(e) {
			e.style.display = 'none';
		});
	} else {
		$$('div .isforum').each(function(e) {
			e.style.display = '';
		});		
	}
}
function forum_delete(id, name)  {
	if(id != 0) {
		new StickyWin({
		  content: StickyWin.ui(CONFIRM, DEL_BOARD.replaceAll('{name}', name), {
		    width: '500px',
		    buttons: [
		      {
		        text: YES,
				onClick: function() {
					new Request({url: '?section=admin&site=forum&func=del&id='+id, 
								onSuccess: function(r) {
									if(r == 'ok') {
										new Fx.Morph('f_' + id, {
											duration: 800,
											onComplete: function(){
												$('f_' + id).destroy();
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
function change_server_display(id) {
	new Request.HTML({url: '?section=admin&site=server&func=switch_display&id='+id+'&ajax=1', 
		update: $('server_overview'),
		evalScripts: true,
		useWaiter: true 
	}).get();
}
function change_server_aktiv(id,ts) {
	new Request.HTML({url: '?section=admin&site=' + (ts ? 'teamspeak' : 'server') + '&func=switch_aktiv&id='+id+'&ajax=1', 
		update: $('server_overview'),
		evalScripts: true,
		useWaiter: true 
	}).get();
}
function change_server_stat(id) {
	new Request.HTML({url: '?section=admin&site=server&func=switch_stat&id='+id+'&ajax=1', 
		update: $('server_overview'),
		evalScripts: true,
		useWaiter: true 
	}).get();
}
function update_server(ts) {
	new Request.HTML({url: '?section=admin&site=' + (ts ? 'teamspeak' : 'server') + '&func=get_server&ajax=1', 
		update: $('server_overview'),
		evalScripts: true,
		useWaiter: true 
	}).get();
}
function server_edit(id) {
	new Fx.Scroll(window, {transition: Fx.Transitions.Elastic.easeOut, duration: 1500}).toElement('server_form');
	new Request.JSON({url: 'ajax_checks.php?func=admin&site=get_server&id='+id, 
		onSuccess: function(data) {
			$('server_form_spinner').style.visibility = 'hidden';
			if(typeof(data.error) === 'undefined') {
				$('server_form').action = '?section=admin&site=server&func=edit&id='+id;
				$('server_form_submit').value = EDIT;
				$('ip').value = data.ip;
				$('port').value = data.port;
				$('queryport').value = data.queryport;
				$('sport').value = data.sport;
				$('passwort').value = data.passwort;																
				$('gamename').value = data.gamename;																				
				(data.displaymenu == 1) ? $('displaymenu').checked = true : $('displaymenu').checked = false; 			
				(data.stat == 1) ? $('stat').checked = true : $('stat').checked = false; 							
				$$('#gametype option').each(function (e) { 
					if(e.value == data.gametype) 
						e.selected = true; 
					else 
						e.selected = false; 
				});													
			} else {
				errorAlert(ERROR, data.error);
			}
		},
		onRequest: function() {
			$('server_form_spinner').style.visibility = '';
		}
	}).get();
}
function ts_edit(id) {
	new Fx.Scroll(window, {transition: Fx.Transitions.Elastic.easeOut, duration: 1500}).toElement('server_form');
	new Request.JSON({url: 'ajax_checks.php?func=admin&site=get_ts&id='+id, 
		onSuccess: function(data) {
			$('server_form_spinner').style.visibility = 'hidden';
			if(typeof(data.error) === 'undefined') {
				$('server_form').action = '?section=admin&site=teamspeak&func=edit&id='+id;
				$('server_form_submit').value = EDIT;
				$('ip').value = data.ip;
				$('port').value = data.port;
				$('qport').value = data.qport;																				
				//(data.aktiv == 1) ? $('displaymenu').checked = true : $('displaymenu').checked = false; 										
				$$('#serverart option').each(function (e) { 
					if(e.value == data.serverart) 
						e.selected = true; 
					else 
						e.selected = false; 
				});													
			} else {
				errorAlert(ERROR, data.error);
			}
		},
		onRequest: function() {
			$('server_form_spinner').style.visibility = '';
		}
	}).get();
}
function server_del(id, ip, ts)  {
	if(id != 0) {
		new StickyWin({
		  content: StickyWin.ui(CONFIRM, DEL_SERVER.replaceAll('{ip}', ip), {
		    width: '500px',
		    buttons: [
		      {
		        text: YES,
				onClick: function() {
					new Request({url: '?section=admin&site=' + (ts ? 'teamspeak' : 'server') + '&func=del&id='+id, 
								onSuccess: function(r) {
									if(r == 'ok') {
										new Fx.Morph('server_' + id, {
											duration: 800,
											onComplete: function(){
												$('server_' + id).destroy();
												check_color('server');
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
function clankasse_edit_auto(id) {
	new Fx.Scroll(window, {transition: Fx.Transitions.Elastic.easeOut, duration: 1500}).toElement('auto_buchung');
	new Request.JSON({url: 'ajax_checks.php?func=admin&site=get_autobuch&id='+id,
		onSuccess: function(data) {
			$('autobuch_submit_spinner').style.visibility = 'hidden';
			if(typeof(data.error) === 'undefined') {
				$('auto_buchung').action = 'ajax_checks.php?func=admin&site=edit_auto&id='+id;
				$('autobuch_submit').value = EDIT;
				$('verwendung').value = data.verwendung;
				$('intervall').value = data.intervall;
				$('betrag').value = data.betrag;																						
				$$('#tagmonat option').each(function (e) { 
					if(e.value == data.tagmonat) 
						e.selected = true; 
					else 
						e.selected = false; 
				});													
			} else {
				errorAlert(ERROR, data.error);
			}
		},
		onRequest: function() {
			$('autobuch_submit_spinner').style.visibility = '';
		}
	}).get();
}
function clankasse_del_auto(id, name)  {
	if(id != 0) {
		new StickyWin({
		  content: StickyWin.ui(CONFIRM, DEL_AUTO_BUCH.replaceAll('{name}', name), {
		    width: '500px',
		    buttons: [
		      {
		        text: YES,
				onClick: function() {
					new Request({url: 'ajax_checks.php?func=admin&site=del_auto&id='+id, 
								onSuccess: function(r) {
									if(r == 'ok') {
										new Fx.Morph('auto_' + id, {
											duration: 800,
											onComplete: function(){
												$('auto_' + id).destroy();
												check_color('auto_buch_overview', 1);
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
function clankasse_edit_user(id) {
	new Fx.Scroll(window, {transition: Fx.Transitions.Elastic.easeOut, duration: 1500}).toElement('user_buch');
	new Request.JSON({url: 'ajax_checks.php?func=admin&site=get_userbuch&id='+id, 
		onSuccess: function(data) {
			$('user_buch_submit_spinner').style.visibility = 'hidden';
			if(typeof(data.error) === 'undefined') {
				$('user_buch').action = 'ajax_checks.php?func=admin&site=edit_userbuch&id='+id;
				$('user_buch_submit').value = EDIT;
				$('userverwendung').value = data.verwendung;
				$('username').value = data.username;
				$('betrag_user').value = data.monatgeld;																																		
			} else {
				errorAlert(ERROR, data.error);
			}
		},
		onRequest: function() {
			$('user_buch_submit_spinner').style.visibility = '';
		}
	}).get();
}
function clankasse_del_buch(id, name)  {
	if(id != 0) {
		new StickyWin({
		  content: StickyWin.ui(CONFIRM, DEL_BUCH.replaceAll('{name}', name), {
		    width: '500px',
		    buttons: [
		      {
		        text: DEL_BUCH_WITH,
				onClick: function() {
					new Request({url: 'ajax_checks.php?func=admin&site=del_buch&id='+id+'&with=1', 
								onSuccess: function(r) {
									if(r == 'ok') {
										update_kontostand();										
										new Fx.Morph('buch_' + id, 'opacity', {
											duration: 800,
											onComplete: function(){
												$('buch_' + id).destroy();
												check_color('buch_overview', 1);
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
		       text: WITHOUT,
				 onClick: function() {
				 new Request({url: 'ajax_checks.php?func=admin&site=del_buch&id='+id, 
								onSuccess: function(r) {
									if(r == 'ok') {
										new Fx.Morph('buch_' + id, {
											duration: 800,
											onComplete: function(){
												$('buch_' + id).destroy();
												check_color('buch_overview', 1);
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
function clankasse_del_user(id, name)  {
	if(id != 0) {
		new StickyWin({
		  content: StickyWin.ui(CONFIRM, DEL_USER_BUCH.replaceAll('{name}', name), {
		    width: '500px',
		    buttons: [
		      {
		        text: YES,
				onClick: function() {
					new Request({url: 'ajax_checks.php?func=admin&site=del_user_buch&id='+id,
								onSuccess: function(r) {
									if(r == 'ok') {										
										new Fx.Morph('user_' + id, 'opacity', {
											duration: 800,
											onComplete: function(){
												$('user_' + id).destroy();
												check_color('user_overview', 1);
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
function switch_clankasse() {
    id = document.getElementById('art');
	if(id.value == '+') {
	    document.getElementById('vonuser').style.display = '';
	    document.getElementById('verwendungby').style.display = '';		
	} else {
	    document.getElementById('vonuser').style.display = 'none';
	    document.getElementById('verwendungby').style.display = 'none';		
	}
}
function update_kontostand() {
	new Request({url: 'ajax_checks.php?func=admin&site=get_kontostand', 
			onSuccess: function(r){
				$('kontostand').value = r;
			}
	
	}).get();
}
function check_menu_vis(el) {
	if(el.options[el.selectedIndex].value == '') {
		$('hide_menu').style.display = 'block';
	} else {
		$('hide_menu').style.display = 'none';
	}
}
function menu_del(id, name)  {
	if(id != 0) {
		new StickyWin({
		  content: StickyWin.ui(CONFIRM, DEL_MENU.replaceAll('{name}', name), {
		    width: '500px',
		    buttons: [
		      {
		        text: YES,
				onClick: function() {
					new Request({url: 'ajax_checks.php?func=admin&site=del_menu&id='+id, 
								onSuccess: function(r) {
									if(r.clean() == 'ok') {										
										new Fx.Morph('m_' + id, {
											duration: 800,
											onComplete: function(){
												$('m_' + id).destroy();
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
function menu_link_edit(id, lang) {
	new Fx.Scroll(window, {transition: Fx.Transitions.Elastic.easeOut, duration: 1500}).toElement('lang_'+lang);
	new Request.JSON({url: 'ajax_checks.php?func=admin&site=get_menu_link&id='+id, 
		onSuccess: function(data) {
			$('lang_spinner_'+lang).style.visibility = 'hidden';
			if(typeof(data.error) === 'undefined') {
				$('lang_'+lang).action = 'ajax_checks.php?func=admin&site=edit_menu_link&id='+id;
				$('submit_'+lang).value = EDIT;
				$('suche_'+lang).value = data.suche;
				$('ersetze_'+lang).value = data.ersetze;																																	
			} else {
				errorAlert(ERROR, data.error);
			}
		},
		onRequest: function() {
			$('lang_spinner_'+lang).style.visibility = '';
		}
	}).get();
}
function menu_link_delete(id, name, lang) {
	if(id != 0) {
		new StickyWin({
		  content: StickyWin.ui(CONFIRM, DEL_MENU_LINK.replaceAll('{name}', name), {
		    width: '500px',
		    buttons: [
		      {
		        text: YES,
				onClick: function() {
					new Request({url: 'ajax_checks.php?func=admin&site=del_menu_link&id='+id, 
								onSuccess: function(r) {
									if(r == 'ok') {										
										new Fx.Morph('item_' + id, {
											duration: 800,
											onComplete: function(){
												$('item_' + id).destroy();
												check_color('overview_' + lang, 1);
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
function links_delete(id, name) {
	if(id != 0) {
		new StickyWin({
		  content: StickyWin.ui(CONFIRM, DEL_LINK.replaceAll('{name}', name), {
		    width: '500px',
		    buttons: [
		      {
		        text: YES,
				onClick: function() {
					new Request({url: 'ajax_checks.php?func=admin&site=del_link&id='+id, 
								onSuccess: function(r) {
									if(r == 'ok') {										
										new Fx.Morph('links_' + id, {
											duration: 800,
											onComplete: function(){
												$('links_' + id).destroy();
												check_color('links_table',1);
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
function links_edit(id) {
	new Fx.Scroll(window, {transition: Fx.Transitions.Elastic.easeOut, duration: 1500}).toElement('links_form');
	new Request.JSON({url: 'ajax_checks.php?func=admin&site=get_link&id='+id, 
		onSuccess: function(data) {
			$('links_form_spinner').style.visibility = 'hidden';
			if(typeof(data.error) === 'undefined') {
				$('links_form').action = '?section=admin&site=links&func=edit&id='+id;
				$('links_form_submit').value = EDIT;
				$('name').value = data.name;
				$('url').value = data.url;
				$('bannerurl').value = data.bannerurl;
				$('beschreibung').value = data.beschreibung;																																												
				tinyMCE.get('beschreibung').setContent(data.beschreibung);							
				$('hits').value = data.hits;	
			} else {
				errorAlert(ERROR, data.error);
			}
		},
		onRequest: function() {
			$('links_form_spinner').style.visibility = '';
		}
	}).get();
}
function gallery_create_folder() {
	win_folder = new StickyWinFxModal({
	  draggable: true,
	  fadeDuration: 700,
	  onClose: function () { this.destroy(); }, 
	  content: StickyWin.ui(GALLERY_ADD_FOLDER, '<input type="text" id="foldername" /> <input type="button" class="submit" id="create_folder" value="'+ADD+'" />', {
		width: '300px'
	  })
	});	
	
	$('create_folder').addEvent('click', function(e) { 
		if($('foldername').value == '') {
			errorAlert(ERROR, GALLERY_INSERT_VAL);
		} else {
			new Request({url: 'ajax_checks.php?func=admin&site=gallery_create_folder',
				 data: 'dirname='+$('foldername').value,
				 onRequest: function() { 
				 	$('create_folder').disabled = true;
				 },
				 onSuccess: function(r) {
				 	$('create_folder').disabled = false;
					if(r == 'ok') {
						el = new Option($('foldername').value, $('foldername').value, false, true);
						$('folder').options[$('folder').length] = el;
						$$('#folder option').each(function (e) { 
								if(e.value == $('foldername').value) 
									e.selected = true; 
								else 
									e.selected = false; 
						});	
						win_folder.hide();
						g_form.validateField('folder');
					} else {
						errorAlert(ERROR, r);
					}
				 }
			}).post();
		}
	});
}
function gallery_kate_delete(id, name) {
	if(id != 0) {
		new StickyWin({
		  content: StickyWin.ui(CONFIRM, DEL_GALLERY_KATE.replaceAll('{name}', name), {
		    width: '500px',
		    buttons: [
		      {
		        text: YES,
				onClick: function() {
					new Request({url: 'ajax_checks.php?func=admin&site=del_gallery_kate&id='+id, 
								onSuccess: function(r) {
									if(r == 'ok') {										
										new Fx.Morph('kate_' + id, {
											duration: 800,
											onComplete: function(){
												$('kate_' + id).destroy();
												check_color('kate_table', 1);
												new Request.HTML({url: 'ajax_checks.php?func=admin&site=get_galleries', 
													update: 'overview_galleries'
												}).get();
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
function gallery_kate_edit(id) {
	new Fx.Scroll(window, {transition: Fx.Transitions.Elastic.easeOut, duration: 1500}).toElement('kategorie');
	new Request.JSON({url: 'ajax_checks.php?func=admin&site=get_gallery_kate&id='+id, 
		onSuccess: function(data) {
			$('kate_submit_spinner').style.visibility = 'hidden';
			if(typeof(data.error) === 'undefined') {
				$('kategorie').action = '?section=admin&site=gallery&func=editkate&id='+id;
				$('kate_submit').value = EDIT;
				$('katename').value = data.katename;
				if(data.access == '') {
					$$('#kateaccess option').each(function (e) { 
						if(e.value == 'all') 
							e.selected = true; 
						else 
							e.selected = false; 
					});
				} else {
					ids = data.access.split(',');
					$$('#kateaccess option').each(function (e) { 
						if(ids.contains(e.value)) 
							e.selected = true; 
						else 
							e.selected = false; 
					});
				}
				$$('#kategorie textarea').each( function (e) { 
					lang = e.name.substr(12);
					el = tinyMCE.get(e.name);
					if(typeof(data['beschreibung'][lang]) === 'undefined') {
						e.value = '';
						el.setContent(''); 
					} else {
						e.value = data['beschreibung'][lang];						
						el.setContent(data['beschreibung'][lang]);								
					}			
				});																																																									
			} else {
				errorAlert(ERROR, data.error);
			}
		},
		onRequest: function() {
			$('kate_submit_spinner').style.visibility = '';
		}
	}).get();
}
function gallery_edit(id) {
	new Fx.Scroll(window, {transition: Fx.Transitions.Elastic.easeOut, duration: 1500}).toElement('gallery');
	new Request.JSON({url: 'ajax_checks.php?func=admin&site=get_gallery&id='+id, 
		onSuccess: function(data) {
			$('gallery_submit_spinner').style.visibility = 'hidden';
			if(typeof(data.error) === 'undefined') {
				$('gallery').action = '?section=admin&site=gallery&func=edit&id='+id;
				$('gallery_submit').value = EDIT;
				$('name').value = data.name;
				exsist = false;
				$$('#folder option').each(function (e) { 
						if(e.value == data.folder) 
							exsist = true;
				});	
				if(!exsist) {
					el = new Option(data.folder, data.folder, false, false);
					el.injectInside('folder');
				}	
				$$('#folder option').each(function (e) { 
						if(e.value == data.folder) 
							e.selected = true; 
						else 
							e.selected = false; 
				});						
				if(data.access == '') {
					$$('#access option').each(function (e) { 
						if(e.value == 'all') 
							e.selected = true; 
						else 
							e.selected = false; 
					});
				} else {
					ids = data.access.split(',');
					$$('#access option').each(function (e) { 
						if(ids.contains(e.value)) 
							e.selected = true; 
						else 
							e.selected = false; 
					});
				}	
				$$('#cID option').each(function (e) { 
						if(e.value == data.cID) 
							e.selected = true; 
						else 
							e.selected = false; 
				});
				if(data.images != 0) 
					$('folder').disabled = true; 
				else 
					$('folder').disabled = false;																																																						
			} else {
				errorAlert(ERROR, data.error);
			}
		},
		onRequest: function() {
			$('gallery_submit_spinner').style.visibility = '';
		}
	}).get();
}
function gallery_delete(id, name) {
	if(id != 0) {
		new StickyWin({
		  content: StickyWin.ui(CONFIRM, DEL_GALLERY.replaceAll('{name}', name), {
		    width: '500px',
		    buttons: [
		      {
		        text: YES,
				onClick: function() {
					new Request({url: 'ajax_checks.php?func=admin&site=del_gallery&id='+id, 
								onSuccess: function(r) {
									if(r == 'ok') {										
										new Fx.Morph('gallery_' + id, {
											duration: 800,
											onComplete: function(){
												$('gallery_' + id).destroy();
												check_color('gallery_table', 1);
												new Request.HTML({
													url: 'ajax_checks.php?func=admin&site=get_gallery_kates',
													update: 'overview_kate'
												}).get();
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
function gallery_set_disc(pid) {
	myConf = new Confirmer({
		msg: 		GALLERY_MSG_SUCCESS,
		pause:		1000,
		 positionOptions: {
			relativeTo: $('disc_'+pid),
	   	 	position: "bottomLeft",
		    offset: {x: -50, y: 10}
		}			
	});
	new Request({url: 'ajax_checks.php?func=admin&site=gallery_set_text&pid='+pid, 
			data: 		'msg='+$('disc_'+pid).get("inputValue"), 
			onComplete: function(r){
				if(r != 'ok') {
					myConf.options.msg = r;
				}
				myConf.prompt();
			}
	}).post();
}
function gallery_del_pic(id) {
	if(id != 0) {
		new StickyWin({
		  content: StickyWin.ui(CONFIRM, DEL_GALLERY_PIC, {
		    width: '500px',
		    buttons: [
		      {
		        text: YES,
				onClick: function() {
					new Request({url: 'ajax_checks.php?func=admin&site=del_gallery_pic&id='+id, 
								onSuccess: function(r) {
									if(r == 'ok') {										
										new Fx.Morph('pic_' + id, {
											duration: 800,
											onComplete: function(){
												$('pic_' + id).destroy();
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
function smilie_delete(id, name) {
	if(id != 0) {
		new StickyWin({
		  content: StickyWin.ui(CONFIRM, DEL_SMILIE.replaceAll('{name}', name), {
		    width: '500px',
		    buttons: [
		      {
		        text: YES,
				onClick: function() {
					new Request({url: 'ajax_checks.php?func=admin&site=del_smilie&id='+id, 
								onSuccess: function(r) {
									if(r == 'ok') {										
										new Fx.Morph('smilie_' + id, {
											duration: 800,
											onComplete: function(){
												$('smilie_' + id).destroy();
												check_color('smilie_table', 1);
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
function smilie_edit(id) {
	win_smilies = new StickyWinFxModal({
	  draggable: true,
	  fadeDuration: 700,
	  onClose: function () { this.destroy(); },
	  content: StickyWin.ui(SMILIE_EDIT, '<div id="smilie_edit"><center><img src="templates/'+DESIGN+'/images/spinner.gif"></center></div>', {
		width: '400px'
	  })
	});
	new Request.HTML({url: 'ajax_checks.php?func=admin&site=smilie_edit&id='+id, 
		update: 'smilie_edit', 
		evalScripts: true
	}).get();			
}
function shout_delete(id, name) {
	if(id != 0) {
		new StickyWin({
		  content: StickyWin.ui(CONFIRM, DEL_SHOUT.replaceAll('{name}', name), {
		    width: '500px',
		    buttons: [
		      {
		        text: YES,
				onClick: function() {
					new Request({url: 'ajax_checks.php?func=admin&site=del_shout&id='+id, 
								onSuccess: function(r) {
									if(r == 'ok') {	
										if (isNaN($('shout_m_'+id))) {
											new Fx.Morph('shout_m_' + id, {
												duration: 800,
												onComplete: function(){
													$('shout_m_' + id).destroy();
													i = 0;
													$$('div .shoutbox_mini_msg').each(function(e){
														e.removeClass('row_odd');
														e.removeClass('row_even');
														if (i % 2) {
															e.set('class', 'row_even');
														}
														else {
															e.set('class', 'row_odd');
														}
														i++;
													});
												}
											}).start({'opacity': [1,0]}); 
										}
										if (isNaN($('shout_'+id))) {
											new Fx.Morph('shout_' + id, {
												duration: 800,
												onComplete: function(){
													$('shout_' + id).destroy();
													i = 0;
													$$('div .shoutbox_msg').each(function(e){
														e.removeClass('row_odd');
														e.removeClass('row_even');
														if (i % 2) {
															e.set('class', 'row_even');
														}
														else {
															e.set('class', 'row_odd');
														}
														i++;
													});
												}
											}).start({'opacity': [1,0]}); 
										}																							
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
function edit_rank(id) {
    scroll_to('rank_form', 1500);
	new Request.JSON({url: 'ajax_checks.php?func=admin&site=get_rank&id='+id, 
		onSuccess: function(data) {
			$('rank_form_spinner').style.visibility = 'hidden';
			if(typeof(data.error) === 'undefined') {
				$('rank_form').addEvent('submit', function(e) {
					if(rank_valid.validate()) {
						if(this.action.contains('edit')) {
							new Event(e).stop();
							new Request({
								url: $('rank_form').action,
								onRequest: function () { 						
									$('rank_form_spinner').style.visibility = '';
									$('rank_form_submit').disabled = true;	
								},
								onSuccess: function(r) {
									$('rank_form_spinner').style.visibility = 'hidden';
									$('rank_form_submit').disabled = false;			
									if(r == 'ok') {			
										make_info(RANK_EDIT_SUCCESS, 'accept');
										$('rank_form').action = '?section=admin&site=ranks&func=add';
										$('rank_form_submit').value = ADD;
										$('rank_form').reset();
										check_fest($('fest'));						
										$('rank_edit').style.display = '';							
										new Request.HTML({url: '?section=admin&site=ranks&func=get_ranks&ajax=1', update: 'ranks_overview', useWaiter: true}).get();											
									} else {
										errorAlert(ERROR, r);
									}
								}
							}).send($('rank_form'));
						} 
					}
				});				
				$('rank_form').action = '?section=admin&site=ranks&func=edit&id='+id;
				$('rank_form_submit').value = EDIT;
				$('rankname').value = data.rankname;
				$('abposts').value = data.abposts;
				$('money').value = data.money;																																																		
				$('fest').checked = (data.fest == 1) ?  true: false;
				$('rank_edit').style.display = 'none';
				check_fest($('fest'));	
			} else {
				errorAlert(ERROR, data.error);
			}
		},
		onRequest: function() {
			$('rank_form_spinner').style.visibility = '';
		}
	}).get();
}
function del_rank(id, name) {
	if(id != 0) {
		new StickyWin({
		  content: StickyWin.ui(CONFIRM, RANK_DEL.replaceAll('{name}', name), {
		    width: '500px',
		    buttons: [
		      {
		        text: YES,
				onClick: function() {
					new Request({url: 'ajax_checks.php?func=admin&site=del_rank&id='+id, 
								onSuccess: function(r) {
									if(r == 'ok') {										
										new Fx.Morph('rank_' + id, {
											duration: 800,
											onComplete: function(){
												$('rank_' + id).destroy();
												check_color('table_ranks', 1);
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
function delete_ban(id, zeit, name) {
	if(id != 0) {
		new StickyWin({
		  content: StickyWin.ui(CONFIRM, DELETE_BAN_CONFIRM.replaceAll('{username}', name), {
		    width: '500px',
		    buttons: [
		      {
		        text: YES,
				onClick: function() {
					new Request({url: 'ajax_checks.php?func=admin&site=del_ban&id='+id+'&zeit='+zeit, 
								onSuccess: function(r) {
									if(r == 'ok') {										
										new Fx.Morph('ban_' + id + '_' + zeit, {
											duration: 800,
											onComplete: function(){
												$('ban_' + id + '_' + zeit).destroy();
												check_color('bans', 1);
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
function user_aktiv(id, name) {
	if(id != 0) {
		new StickyWin({
		  content: StickyWin.ui(CONFIRM, USER_AKTIVIEREN.replaceAll('{username}', name), {
		    width: '500px',
		    buttons: [
		      {
		        text: YES,
				onClick: function() {
					new Request({url: 'ajax_checks.php?func=admin&site=user_aktiv&id='+id, 
								onSuccess: function(r) {
									if(r == 'ok') {										
										new Fx.Morph('unaktiv_' + id, {
											duration: 800,
											onComplete: function(){
												$('unaktiv_' + id).destroy();
												check_color('unaktivs', 1);
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
function find_user() {
	new Request.HTML({url: 'ajax_checks.php?func=admin&site=find_user', 
		update: 'user_suche_result',
		useWaiter: true,
		data: 'suche='+$('suche').get("inputValue")+'&suchart='+$('suchart').get("inputValue")
	}).post();
}
function edit_cms(id) {
	scroll_to('cms_form', 1500);
	new Request.JSON({url: 'ajax_checks.php?func=admin&site=get_cms&id='+id, 
		onSuccess: function(data) {
			$('cms_spinner').style.visibility = 'hidden';
			if(typeof(data.error) === 'undefined') {
				$('cms_form').action = '?section=admin&site=cms&func=edit&id='+id;
				$('cms_submit').value = EDIT;
				if(data.access == '') {
					$$('#rights option').each(function (e) { 
						if(e.value == 'all') 
							e.selected = true; 
						else 
							e.selected = false; 
					});
				} else {
					ids = data.access.split(',');
					$$('#rights option').each(function (e) { 
						if(ids.contains(e.value)) 
							e.selected = true; 
						else 
							e.selected = false; 
					});
				}													
				$$('#cms_form textarea').each( function (e) { 
					lang = e.name.substr(12);
					el = tinyMCE.get(e.name);
					if(typeof(data['content'][lang]) === 'undefined') {
						e.value = '';
						el.setContent(''); 
					} else {
						e.value = data['content'][lang];						
						el.setContent(data['content'][lang]);								
					}			
				});
				$$('#cms_form input.headline1').each( function (e) { 
					lang = e.name.substr(9);
					if(typeof(data['headline'][lang]) === 'undefined') {
						e.value = '';
					} else {
						e.value = data['headline'][lang];							
					}				
				});				
			} else {
				errorAlert(ERROR, data.error);
			}
		},
		onRequest: function() {
			$('cms_spinner').style.visibility = '';
		}
	}).get();
}
function delete_cms(id, name) {
	if(id != 0) {
		new StickyWin({
		  content: StickyWin.ui(CONFIRM, CMS_DELTE.replaceAll('{name}', name), {
		    width: '500px',
		    buttons: [
		      {
		        text: YES,
				onClick: function() {
					new Request({url: 'ajax_checks.php?func=admin&site=del_cms&id='+id,
								onComplete: function(r) {
									if(r == 'ok') {										
										new Fx.Morph('cms_' + id, {
											duration: 800,
											onComplete: function(){
												$('cms_' + id).destroy();
												check_color('cms_overview', 1);
											}
										}).start({'opacity':[1,0]}); 					
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
function edit_cal(id) {
	scroll_to('calendar_form', 1500);
	new Request.JSON({url: 'ajax_checks.php?func=admin&site=get_event&id='+id, 
		onSuccess: function(data) {
			$('cal_spinner').style.visibility = 'hidden';
			if(typeof(data.error) === 'undefined') {
				$('calendar_form').action = '?section=admin&site=calendar&func=edit&id='+id;
				$('calendar_submit').value = EDIT;
				$('eventname').value = data.eventname;
				$('datum').value = data.datum;				
				if(data.access == '') {
					$$('#rights option').each(function (e) { 
						if(e.value == 'all') 
							e.selected = true; 
						else 
							e.selected = false; 
					});
				} else {
					ids = data.access.split(',');
					$$('#rights option').each(function (e) { 
						if(ids.contains(e.value)) 
							e.selected = true; 
						else 
							e.selected = false; 
					});
				}													
				$$('#calendar_form textarea').each( function (e) { 
					lang = e.name.substr(12);
					el = tinyMCE.get(e.name);
					if(typeof(data['inhalt'][lang]) === 'undefined') {
						e.value = '';
						el.setContent(''); 
					} else {
						e.value = data['inhalt'][lang];						
						el.setContent(data['inhalt'][lang]);								
					}			
				});			
			} else {
				errorAlert(ERROR, data.error);
			}
		},
		onRequest: function() {
			$('cal_spinner').style.visibility = '';
		}
	}).get();
}
function del_cal(id, name) {
	if(id != 0) {
		new StickyWin({
		  content: StickyWin.ui(CONFIRM, CAL_DELETE.replaceAll('{name}', name), {
		    width: '500px',
		    buttons: [
		      {
		        text: YES,
				onClick: function() {
					new Request({url: 'ajax_checks.php?func=admin&site=del_cal&id='+id, 
								onSuccess: function(r) {
									if(r == 'ok') {										
										var morph = new Fx.Morph('cal_'+id,{duration:800, onComplete: function(){
												$('cal_' + id).destroy();
												check_color('cal_table', 1);
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
}
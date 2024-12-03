jQuery(document).ready(function () {
    window.ifsoLocGenPipe = {
        changing_input : null,
        open: function (url,el) {
            var input_name = jQuery(el).closest('label').attr('for');
            if(typeof (input_name)!=='undefined'){
                var inp =jQuery(el).closest('.elementor-control-field').find('#' + input_name);
                this.changing_input = inp[0];
            }
            window.open(url + "&ui_type=adder", 'newwindow', 'width=800,height=600');
        },
        accept: function (data) {
            if(typeof(this.changing_input)!=='undefined' && this.changing_input!==null){
                this.changing_input.focus();
                this.changing_input.value = data;
                this.changing_input.dispatchEvent(new KeyboardEvent('keyup', {code: 'Enter', key: 'Enter', charCode: 13, keyCode: 13, view: window, bubbles: true,which:13}));
                this.changing_input.blur();
            }
        }
    }
    var subGroups = function(multibox){
        this.multibox = multibox;
        this.reset();
    };
    subGroups.prototype = {
        processElement : function (field,element,condition){
            if(this.condition===null)
                this.condition = condition;
            if (field['is_switcher']){
                this.processSwitcherElement(field,element);
            }
            if(field['subgroup']!==null){
                this.processSubgroupElement(field,element);
            }
        },

        processSubgroupElement : function(field,element){
            var subgroupName = field.subgroup;
            if(typeof(this.groups[subgroupName])==='undefined'){
                this.groups[subgroupName] = [];
                this.groupModelFields[subgroupName] = [];
            }
            this.groups[subgroupName].push(element);
            this.groupModelFields[subgroupName].push(field);
        },

        processSwitcherElement : function(field,element){
            if(this.switcher===null){
                var _this = this;
                this.switchedHandler = _this.switcherChanged.bind(_this);
                this.switcher = element.querySelector('select');
                if(!this.switcherActive) this.switcher.addEventListener('change',_this.switchedHandler);
                this.switcherActive =  true;
            }
        },

        switcherChanged : function (_this,initialCall=false) {
            var _this = this;
            if(null===_this.switcher)return false;
            _this.activeSubgroup = _this.switcher.value;
            Object.keys(_this.groups).forEach(function(groupName){
                if (groupName===_this.activeSubgroup){
                    jQuery(_this.groups[groupName]).removeClass('nodisplay');
                }
                else{
                    jQuery(_this.groups[groupName]).addClass('nodisplay');
                    _this.groups[groupName].forEach(function (el,i) {
                        reset_condition_control(make_elementor_control_name(_this.condition,_this.groupModelFields[groupName][i]['name']));
                    })
                }
            });
            //if(!initialCall) _this.multibox.resetDataContainer();
        },

        elementsLoaded : function(){
            //console.log('elements loaded!');
            this.switcherChanged(this,true);
        },

        reset : function () {
            if(this.switcher!==null && typeof(this.switcher)!=='undefined')
                this.switcher.removeEventListener('change',this.switchedHandler);
            this.activeSubgroup = null;
            this.groups = {};
            this.groupModelFields = {};
            this.switcher = null;
            this.switcherActive = false;
            this.switchedHandler = null;
            this.condition = null;
        },
    };
    var multiboxDataContainer = function(data=null){
        this.data_separator = '!!';
        this.version_separator = '^^';
        this.reset();
        if(data!==null)this.data=data;
    };
    multiboxDataContainer.prototype = {
        getVersions : function(){
            return (this.data==='') ? [] : this.data.split(this.version_separator);
        },
        addVersion : function (toAdd){
            if(!this.data.includes(toAdd)){
                this.data += (this.data!=='') ? this.version_separator : '';
                this.data += toAdd;
            }
        },
        removeVersion : function (removeId){
            var versions = this.getVersions();
            versions.splice(removeId,1);
            this.data = versions.join(this.version_separator);
        },
        createNewLocation : function (locationType, behindSceneLocationData, visualLocationData) {
            var data = [locationType, visualLocationData, behindSceneLocationData];
            return data.join(this.data_separator);
        },
        reset : function(){
            this.container = null;
            this.data = '';
            this.current_version_data = null;
        }
    };
    var multibox = function(){
        this.geo_symbols = ['CITY','COUNTRY','STATE','CONTINENT','TIMEZONE'];
        this.min_redraw_interval = 1000;
        this.sym_inputs = [];
        this.symInputChangedCallback = this.symInputChanged.bind(this);
        this.symInputKeyupCallback = this.symInputKeyup.bind(this);
        this.versionDeleteCallback = this.deleteButtonCallback.bind(this);
        this.previouslyRendered = {};
        this.data_container_controller = new multiboxDataContainer();
        this.country_cache = {};

        this.reset();
    };
    multibox.prototype = {
        processElement : function(field,element,condition){
            if(field['symbol']!==null && field['symbol']){
                if(this.condition === null){this.condition = condition;}
                var elType = (field.type==='text' || field.type==='checkbox') ? 'input' : 'select';
                var el = element.querySelector(elType);
                var obj = {'field':field,'element':el};
                this.sym_inputs.push(obj);
                el.addEventListener('input',this.symInputChangedCallback);
                if(elType==='input'){
                    el.addEventListener('keyup',this.symInputKeyupCallback);
                    el.addEventListener('blur',this.symInputKeyupCallback);
                }
                if(elType==='select')
                    el.addEventListener('change',this.symInputKeyupCallback);
            }
            if(field['type'] === 'multi'){
                this.data_container_controller.container = {'field':field,'element':element.querySelector('input')};
                this.data_container_controller.data = element.querySelector('input').value;
                this.data_container = this.data_container_controller.container;
            }
        },
        symInputChanged : function(event){
            var _this = this;
            var changed_obj = null;
            var changed_input_val = event.target.value;
            this.sym_inputs.forEach(function (obj){
                if(event.target===obj.element){
                    changed_obj = obj;
                    return;
                }
            });
            if(changed_obj===null || changed_input_val==='')
                return;
            var current_version_dc = new multiboxDataContainer(this.data_container_controller.data);
            if(this.geo_symbols.includes(changed_obj.field.symbol)){
                var newVals = this.parseInputValue(changed_input_val,changed_obj.field.symbol);
                newVals.forEach(function (newVal){
                    current_version_dc.addVersion(current_version_dc.createNewLocation(newVal['loc_type'],newVal['loc_val'],newVal['loc_val']));
                    if(newVal['loc_type']==='COUNTRY' && event.target.tagName==='SELECT')
                        _this.country_cache[newVal['loc_val']] = event.target.querySelector('option[value="'+ newVal['loc_val'] +'"]').innerHTML;
                })
            }
            else
                current_version_dc.addVersion(current_version_dc.createNewLocation(changed_obj.field.symbol,this.sym_inputs[0].element.value,this.sym_inputs[1].element.value));
            this.data_container_controller.current_version_data = current_version_dc;
            //this.renderMultiboxUI(this.condition);
        },
        symInputKeyup : function (event){
            if(jQuery(event.target).closest('.nosubmit').length>0) return;
            if(event.which===13 || event.type==='blur' || event.target.tagName==='SELECT'){               //enter was pressed
                if(this.data_container_controller.current_version_data===null) this.symInputChanged(event);
                if(typeof(this.data_container)!=='undefined' && this.data_container!==null && this.data_container_controller.current_version_data!==null){
                    this.data_container_controller = this.data_container_controller.current_version_data;
                    this.data_container_controller.current_version_data = null;
                    set_condition_control(make_elementor_control_name(this.condition,this.data_container.field.name),this.data_container_controller.data);
                    this.renderMultiboxUI(this.condition);
                    reset_condition_control(event.target.getAttribute('data-setting'));
                }
            }
        },
        deleteButtonCallback : function(event){
            var wrap = event.target.parentElement;
            var version_id = wrap.getAttribute('version_number');
            this.data_container_controller.removeVersion(version_id);
            set_condition_control(make_elementor_control_name(this.condition,this.data_container.field.name),this.data_container_controller.data);
            this.renderMultiboxUI(this.condition);
        },
        resetDataContainer : function (){
            reset_condition_control(make_elementor_control_name(this.condition,this.data_container.field.name));
        },
        reset : function(){
            this.sym_inputs.forEach(function(obj){
                obj.element.removeEventListener('input',this.symInputChangedCallback);
                obj.element.removeEventListener('keyup',this.symInputKeyup);
                obj.element.removeEventListener('blur',this.symInputKeyup);
                obj.element.removeEventListener('change',this.symInputKeyup);
            });
            this.condition = null;
            this.data_container_controller.reset();
            this.data_container = null;
            this.sym_inputs = [];
        },
        renderMultiboxUI : function (type){
            var wrapper_el = document.querySelector('.ifso-multibox-wrapper');
            var wrap_elementor_control = jQuery(wrapper_el).closest('.elementor-control');
            var desc_el = wrapper_el.querySelector('.ifso-multibox-description');
            var versions_wrap_el = wrapper_el.querySelector('.ifso-multibox-versions');
            var invalidate = typeof(this.previouslyRendered)!=='undefined' && Date.now() > this.previouslyRendered.time + this.min_redraw_interval;

            if(type===null || type === "" || typeof(data_rules_model[type]['multibox'])==='undefined'){
                jQuery(wrap_elementor_control).hide();
                return;
            }
            jQuery(wrap_elementor_control).show();

            if(this.previouslyRendered.condition!==type || invalidate)desc_el.innerHTML = data_rules_model[type]['multibox'].description;

            if((this.data_container!==null && this.data_container_controller.data!==this.previouslyRendered.data_container) || invalidate){
                versions_wrap_el.innerHTML = '<span class="no-versions-text">No targets selected</span>';
                var versions = this.data_container_controller.getVersions();
                if(versions.length!==0){
                    versions_wrap_el.innerHTML = '';
                    for(var i=0;i<versions.length;i++){
                        var v_data = versions[i].split(this.data_container_controller.data_separator);
                        if(typeof(v_data[1])!=='undefined'){
                            var label = ''
                            var display_value = v_data[1];
                            switch(this.condition){
                                case 'Geolocation':
                                    label = v_data[0];
                                    if(label==='COUNTRY'){
                                        if(typeof(this.country_cache[display_value])==='undefined')
                                            this.country_cache[display_value] = this.searchDRModelForCountryName(display_value);
                                        if(typeof(this.country_cache[display_value])!=='undefined')
                                            display_value = this.country_cache[display_value];
                                    }
                                    break;
                                case 'PageVisit':
                                    label = v_data[2];
                                    break;
                            }
                            var v_el = this.createVersionElement(display_value,label);
                            v_el.setAttribute('version_number',i);
                            v_el.addEventListener('click',this.versionDeleteCallback);
                            versions_wrap_el.appendChild(v_el);
                        }
                    }
                }
            }
            this.previouslyRendered = {condition:type,data_container:this.data_container_controller.data,time:Date.now()};
        },
        createVersionElement : function(value,label=''){
            var versionEl = document.createElement('div');
            versionEl.className = 'ifso-multibox-version';
            var contentEl = document.createElement('span');
            contentEl.innerHTML = value;
            var label_wrap = document.createElement('div');
            label_wrap.className = 'content-label';
            label_wrap.appendChild(document.createTextNode( label.toLowerCase() + '\u00A0:\u00A0'));
            label_wrap.appendChild(contentEl);
            var delBtn = document.createElement('button');
            delBtn.innerHTML = 'X';
            versionEl.appendChild(label_wrap);
            versionEl.appendChild(delBtn);
            return versionEl;
        },
        parseInputValue : function(val,symbol=''){
            try{return JSON.parse(val);}
            catch(e){return [{loc_type:symbol,loc_val:val}];}
        },
        searchDRModelForCountryName : function (countryCode){
            if(data_rules_model['Geolocation']['fields']['geolocation_country_input']['options']===null) return countryCode;
            for (var i = 0; i < data_rules_model['Geolocation']['fields']['geolocation_country_input']['options'].length; i++){
                var countryOpt = data_rules_model['Geolocation']['fields']['geolocation_country_input']['options'][i];
                if(countryOpt.value === countryCode){
                    return countryOpt.display_value;
                }
            }
        },
    };

    var data_rules_model = (typeof(data_rules_model_json)==='string') ? JSON.parse(data_rules_model_json) : data_rules_model_json;
    console.log(data_rules_model);
    var license_data = (typeof(license_data_json)==='string') ? JSON.parse(license_data_json) : license_data_json;
    var ifso_conditions_ui_observer = null;
    var prev_opened_model = null;
    var theMultibox = new multibox();
    var currentSubgroups = new subGroups(theMultibox);
    var ui_theme = null;

    elementor.hooks.addAction( 'panel/open_editor/widget', elementOpenedCB );
    elementor.hooks.addAction( 'panel/open_editor/section', elementOpenedCB );
    elementor.hooks.addAction( 'panel/open_editor/column', elementOpenedCB );
    elementor.hooks.addAction( 'panel/open_editor/container', elementOpenedCB );

    function elementOpenedCB(panel, model, view){
        prev_opened_model = model;
        if(ifso_conditions_ui_observer===null){
            var panelEl = panel.content.el;
            ifso_conditions_ui_observer = new MutationObserver(refreshConditonsUI);
            ifso_conditions_ui_observer.observe(panelEl,{  childList: true,subtree:true, attributes: false});
        }
    }

    function refreshConditonsUI(mutations){
        var panel = document.getElementById('elementor-controls');
        if(null!==panel){
            var conditionTypeSelect = panel.querySelector('.elementor-control-ifso_condition_type select');
            update_ui_theme_class();
            if(null!==conditionTypeSelect){
                conditionTypeSelect.addEventListener('change',refreshConditonsUI);
                var selectedConditionType = conditionTypeSelect.value;
                var elements = {};
                currentSubgroups.reset();
                theMultibox.reset();
                Object.keys(data_rules_model).forEach(function (condName){
                    var display = (selectedConditionType===condName);
                    var displayClass = (display) ? 'displayme' : 'nodisplay';
                    var nonDisplayClass = (display) ? 'nodisplay' : 'displayme';
                    elements[condName] = []
                    Object.keys(data_rules_model[condName]['fields']).forEach(function (fieldname){
                        var elementor_control_name = make_elementor_control_name(condName,fieldname);
                        if(!display) reset_condition_control(elementor_control_name);
                        var el = panel.querySelector('.elementor-control-' + elementor_control_name);
                        if(el!==null){
                            var model_field = data_rules_model[condName]['fields'][fieldname];
                            if(display){
                                currentSubgroups.processElement(model_field,el,condName);
                                theMultibox.processElement(model_field,el,condName);
                            }
                            elements[condName].push(el);
                        }
                    });

                    if(condName==='Geolocation'){   //Temporary solution
                        var notices = Array.prototype.slice.call(panel.querySelectorAll('.ifso_notice.ifso_notice_'+condName));
                        if(notices.length>0){   //Hide the elementor control itself to avoid invisible margins etc
                            var notices_wrap = jQuery(notices[0]).closest('.elementor-control');
                            elements[condName] = elements[condName].concat(notices_wrap[0]);
                        }
                    }

                    jQuery(elements[condName]).addClass(displayClass);
                    jQuery(elements[condName]).removeClass(nonDisplayClass);
                });
                theMultibox.renderMultiboxUI(selectedConditionType);
                handle_error_notice(selectedConditionType,license_data,panel);
                currentSubgroups.elementsLoaded();
            }
        }
    }

    function handle_error_notice(selectedCondition,license_data,panel) {
        var license_error_notice = panel.querySelector('.ifso-license-error');
        if(license_error_notice!==null && typeof(license_error_notice)!=='undefined'){
            jQuery(license_error_notice).addClass('nodisplay');
        }
        if(!license_data['is_license_valid'] && selectedCondition && !in_array(license_data['free_conditions'],selectedCondition)){
            jQuery(panel.querySelector('.ifso-license-error')).removeClass('nodisplay');
        }
    }

    function make_elementor_control_name(condName,fieldName) {
        return condName + '-' + fieldName;  //before: fieldname
    }

    function reset_condition_control(control_name){
        if(prev_opened_model!==null){
            //prev_opened_model.getSetting('setting-name');
            var default_value = prev_opened_model.attributes.settings.defaults[control_name];
            prev_opened_model.setSetting(control_name,default_value);
        }
    }

    function set_condition_control(control_name,val){
        if(prev_opened_model!==null){
            prev_opened_model.setSetting(control_name,val);
        }
    }

    function in_array(array,member){
        if(array.indexOf(member)===-1){
            return false;
        }
        return true;
    }

    function update_ui_theme_class(){
        var body_class_base = 'editor_ui_theme_';
        if(ui_theme!==elementor.getPreferences('ui_theme')){
            if(ui_theme!==null)
                document.body.classList.remove(body_class_base+ui_theme)
            ui_theme = elementor.getPreferences('ui_theme');
            document.body.classList.add(body_class_base+ui_theme);
        }
    }
});
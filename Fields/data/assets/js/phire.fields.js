/**
 * Fields Module Scripts for Phire CMS 2
 */

phire.validatorCount = 1;
phire.modelCount     = 1;
phire.curFieldValue  = null;
phire.curFields      = {};
phire.editorIds      = [];

phire.toggleEditor = function(sel) {
    if (jax(sel).val().indexOf('textarea') != -1) {
        jax('#editor').show();
    } else {
        jax('#editor').hide();
    }
};

phire.addValidator = function() {
    phire.validatorCount++;

    // Add validator select field
    jax('#validator_new_1').clone({
        "name" : 'validator_new_' + phire.validatorCount,
        "id"   : 'validator_new_' + phire.validatorCount
    }).appendTo(jax('#validator_new_1').parent());

    // Add validator value text field
    jax('#validator_value_new_1').clone({
        "name" : 'validator_value_new_' + phire.validatorCount,
        "id"   : 'validator_value_new_' + phire.validatorCount
    }).appendTo(jax('#validator_value_new_1').parent());

    // Add validator message text field
    jax('#validator_message_new_1').clone({
        "name" : 'validator_message_new_' + phire.validatorCount,
        "id"   : 'validator_message_new_' + phire.validatorCount
    }).appendTo(jax('#validator_message_new_1').parent());
};

phire.addModel = function() {
    phire.modelCount++;

    // Add model select field
    jax('#model_new_1').clone({
        "name" : 'model_new_' + phire.modelCount,
        "id"   : 'model_new_' + phire.modelCount
    }).appendTo(jax('#model_new_1').parent());

    // Add model type text field
    jax('#model_type_new_1').clone({
        "name" : 'model_type_new_' + phire.modelCount,
        "id"   : 'model_type_new_' + phire.modelCount
    }).appendTo(jax('#model_type_new_1').parent());
};

phire.addField = function(fid, values) {
    var fieldName = 'field_' + fid;
    var values    = (values != undefined) ? values : {};
    if (fieldName.substr(-2) == '[]') {
        fieldName = fieldName.substring(0, (fieldName.length - 2));
    }

    if (phire.curFields[fieldName] != undefined) {
        phire.curFields[fieldName]++;
    } else {
        phire.curFields[fieldName] = 1;
    }

    var cur = phire.curFields[fieldName];

    if (values[fieldName] == undefined) {
        values[fieldName] = [''];
    }

    for (var j = 0; j < values[fieldName].length; j++) {
        var oldName = fieldName;
        var newName = oldName + '_' + cur;
        var oldObj  = jax('#' + oldName)[0];
        var tab     = (jax('#' + oldName).attrib('tabindex') != null) ?
            (parseInt(jax('#' + oldName).attrib('tabindex')) + (1000 * cur)) : null;

        // If the object is a checkbox or radio set, clone the fieldset
        if ((oldObj.type == 'checkbox') || (oldObj.type == 'radio')) {
            var fldSet = jax(oldObj).parent();
            var fldSetInputs = fldSet.getElementsByTagName('input');
            var fldSetSpans  = fldSet.getElementsByTagName('span');
            var vals = {};
            var mrk  = [];
            if (values[fieldName][j] != '') {
                mrk = values[fieldName][j];
                for (var k = 0; k < fldSetInputs.length; k++) {
                    if (fldSetSpans[k] != undefined) {
                        vals[fldSetInputs[k].value.toString()] = fldSetSpans[k].innerHTML;
                    } else {
                        vals[fldSetInputs[k].value.toString()] = fldSetInputs[k].value.toString();
                    }
                }
            } else {
                for (var k = 0; k < fldSetInputs.length; k++) {
                    if (fldSetSpans[k] != undefined) {
                        vals[fldSetInputs[k].value.toString()] = fldSetSpans[k].innerHTML;
                    } else {
                        vals[fldSetInputs[k].value.toString()] = fldSetInputs[k].value.toString();
                    }
                    if (fldSetInputs[k].checked) {
                        mrk.push(fldSetInputs[k].value);
                    }
                }
            }

            var fldSetParent = jax(fldSet).parent();
            if (oldObj.type == 'checkbox') {
                var attribs = {"name": newName + '[]', "id": newName};
                if (tab != null) {
                    attribs.tabindex = tab;
                }
                jax(fldSetParent).appendCheckbox(vals, attribs, mrk);
            } else {
                var attribs = {"name": newName, "id": newName};
                if (tab != null) {
                    attribs.tabindex = tab;
                }
                jax(fldSetParent).appendRadio(vals, attribs, mrk);
            }
            // Else, clone the input or select
        } else {
            var realNewName = ((oldObj.nodeName == 'SELECT') && (oldObj.getAttribute('multiple') != undefined)) ?
            newName + '[]' : newName;
            var attribs = {
                "name": realNewName,
                "id": newName
            };
            if (tab != null) {
                attribs.tabindex = tab;
            }
            jax('#' + oldName).clone(attribs).appendTo(jax('#' + oldName).parent());

            if (jax('#' + newName)[0].nodeName == 'SELECT') {
                var opts = jax('#' + newName + ' > option');

                for (var o = 0; o < opts.length; o++) {
                    opts[o].selected = false;
                }
            }

            if (jax('#' + newName)[0].type != 'file') {
                jax('#' + newName).val('');
                if (values[fieldName][j] != '') {
                    jax('#' + newName).val(values[fieldName][j]);
                }
            } else {
                if ((jax('#rm_field_file_' + fid)[0] != undefined) && (values[fieldName][j] != '')) {
                    var fileFieldSetParent = jax(jax('#rm_field_file_' + fid).parent()).parent();
                    var fileValues = {};
                    var filePath   = '#';

                    if (jax.cookie.load('phire') != '') {
                        var phireCookie = jax.cookie.load('phire');
                        filePath = phireCookie.fields_upload_folder + '/' + values[fieldName][j];
                    }

                    fileValues[values[fieldName][j].toString()] = 'Remove <a href="' + filePath + '" target="_blank">' + values[fieldName][j] + '</a>?';
                    jax(fileFieldSetParent).appendCheckbox(fileValues, {
                        "name": 'rm_field_file_' + fid + '_' + (j + 1) + '[]',
                        "id": 'rm_field_file_' + fid + '_' + (j + 1)
                    });
                }
            }
        }
        phire.curFields[fieldName] = cur;
        cur++;
    }

    return false;
};

phire.getModelTypes = function(sel, path, cur) {
    if (jax(sel).val() != '----') {
        var cur   = (cur) ? 'cur' : 'new';
        var id    = sel.id.substring(sel.id.lastIndexOf('_') + 1);
        var opts  = jax('#model_type_' + cur + '_' + id + ' > option').toArray();
        var start = opts.length - 1;
        for (var i = start; i >= 0; i--) {
            jax(opts[i]).remove();
        }
        jax('#model_type_' + cur + '_' + id).append('option', {"value" : '----'}, '----');

        if (jax(sel).val() != '----') {
            var json = jax.get(path + '/fields/json/' + jax(sel).val());
            if (json.length > 0) {
                for (var i = 0; i < json.length; i++) {
                    jax('#model_type_' + cur + '_' + id).append('option', {
                        "value" : json[i].type_field + '|' + json[i].type_value
                    }, json[i].type_name);
                }
            }
        }
    }
};

phire.changeHistory = function(sel, path) {
    var ids     = sel.id.substring(sel.id.indexOf('_') + 1).split('_');
    var modelId = ids[0];
    var fieldId = ids[1];
    var marked  = jax('#' + sel.id + ' > option:selected').val();

    if ((phire.curFieldValue == null) && (jax('#field_' + fieldId)[0] != undefined)) {
        phire.curFieldValue = jax('#field_' + fieldId).val();
    }

    if (marked != 0) {
        var j = jax.json.parse(path + '/fields/json/' + modelId + '/' + fieldId + '/' + marked);
        if (jax('#field_' + j.fieldId)[0] != undefined) {
            if (typeof CKEDITOR !== 'undefined') {
                if (CKEDITOR.instances['field_' + j.fieldId] != undefined) {
                    CKEDITOR.instances['field_' + j.fieldId].setData(j.value);
                }
            } else if (typeof tinymce !== 'undefined') {
                tinymce.activeEditor.setContent(j.value);
            }
            jax('#field_' + j.fieldId).val(j.value);
        }
    } else {
        if (jax('#field_' + fieldId)[0] != undefined) {
            if (typeof CKEDITOR !== 'undefined') {
                if (CKEDITOR.instances['field_' + fieldId] != undefined) {
                    CKEDITOR.instances['field_' + fieldId].setData(phire.curFieldValue);
                }
            } else if (typeof tinymce !== 'undefined') {
                tinymce.activeEditor.setContent(phire.curFieldValue);
            }
            jax('#field_' + fieldId).val(phire.curFieldValue);
        }
    }
};

phire.loadEditor = function(editor, id) {
    if (null != id) {
        var w = Math.round(jax('#field_' + id).width());
        var h = Math.round(jax('#field_' + id).height());
        phire.editorIds = [{ "id" : id, "width" : w, "height" : h }];
    }

    var sysPath = '';
    if (jax.cookie.load('phire') != '') {
        var phireCookie = jax.cookie.load('phire');
        sysPath = phireCookie.base_path + phireCookie.app_uri;
    }

    if (phire.editorIds.length > 0) {
        for (var i = 0; i < phire.editorIds.length; i++) {
            if (editor == 'ckeditor') {
                if (CKEDITOR.instances['field_' + phire.editorIds[i].id] == undefined) {
                    CKEDITOR.replace(
                        'field_' + phire.editorIds[i].id,
                        {
                            width                   : 'auto',
                            height                  : phire.editorIds[i].height,
                            allowedContent          : true,
                            filebrowserBrowseUrl    : sysPath + '/fields/browser?editor=ckeditor&type=file',
                            filebrowserWindowWidth  : '960',
                            filebrowserWindowHeight : '720'
                        }
                    );
                }
                var eid = phire.editorIds[i].id;
                jax('#field_' + eid).keyup(function(){
                    console.log(jax('#field_' + eid).val());
                    CKEDITOR.instances['field_' + eid].setData(jax('#field_' + eid).val());
                });
            } else if (editor == 'tinymce') {
                if (tinymce.editors['field_' + phire.editorIds[i].id] == undefined) {
                    tinymce.init(
                        {
                            selector              : "textarea#field_" + phire.editorIds[i].id,
                            theme                 : "modern",
                            plugins: [
                                "advlist autolink lists link image hr", "searchreplace wordcount code fullscreen",
                                "table", "template paste textcolor"
                            ],
                            image_advtab          : true,
                            toolbar1              : "insertfile undo redo | styleselect | forecolor backcolor | bold italic underline strikethrough | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | table | link image",
                            width                 : 'auto',
                            height                : phire.editorIds[i].height,
                            relative_urls         : false,
                            convert_urls          : 0,
                            remove_script_host    : 0,
                            file_browser_callback : function(field_name, url, type, win) {
                                tinymce.activeEditor.windowManager.open({
                                    title  : "Asset Browser",
                                    url    : sysPath + '/fields/browser?editor=tinymce&type=' + type,
                                    width  : 960,
                                    height : 720
                                }, {
                                    oninsert : function(url) {
                                        win.document.getElementById(field_name).value = url;
                                    }
                                });
                            }
                        }
                    );
                } else {
                    tinymce.get('field_' + phire.editorIds[i].id).show();
                }
                var eid = phire.editorIds[i].id;
                jax('#field_' + eid).keyup(function(){
                    tinymce.editors['field_' + eid].setContent(jax('#field_' + eid).val());
                });
            }
        }
    }
};

phire.changeEditor = function() {
    var editor = jax(this).data('editor');
    var id     = jax(this).data('fid');
    if (this.innerHTML == 'Source') {
        this.innerHTML = 'Editor';
        if (typeof CKEDITOR !== 'undefined') {
            content = CKEDITOR.instances['field_' + id].getData();
            CKEDITOR.instances['field_' + id].destroy();
        } else if (typeof tinymce !== 'undefined') {
            content = tinymce.activeEditor.getContent();
            tinymce.get('field_' + id).hide();
        }
        jax('#field_' + id).val(content);
        jax('#field_' + id).show();
    } else {
        this.innerHTML = 'Source';
        if (editor == 'ckeditor') {
            phire.loadEditor('ckeditor', id);
        } else if (editor == 'tinymce') {
            phire.loadEditor('tinymce', id);
        }
    }

    return false;
};

jax(document).ready(function(){
    if (jax('#fields-form')[0] != undefined) {
        jax('#checkall').click(function(){
            if (this.checked) {
                jax('#fields-form').checkAll(this.value);
            } else {
                jax('#fields-form').uncheckAll(this.value);
            }
        });
        jax('#fields-form').submit(function(){
            return jax('#fields-form').checkValidate('checkbox', true);
        });
    }
    if (jax('#field-groups-form')[0] != undefined) {
        jax('#checkall').click(function(){
            if (this.checked) {
                jax('#field-groups-form').checkAll(this.value);
            } else {
                jax('#field-groups-form').uncheckAll(this.value);
            }
        });
        jax('#field-groups-form').submit(function(){
            return jax('#field-groups-form').checkValidate('checkbox', true);
        });
    }
    if (jax('form')[0] != undefined) {
        var forms   = jax('form').toArray();
        var fields  = [];
        var path    = null;

        for (var i = 0; i < forms.length; i++) {
            for (var name in forms[i]) {
                if ((forms[i][name] != undefined) && (forms[i][name] != null) && (forms[i][name].name != undefined) &&
                    (typeof forms[i][name].name.substring == 'function') && (forms[i][name].name.substring(0, 6) == 'field_') &&
                    (fields.indexOf(forms[i][name].name.substring(6)) == -1)) {
                    fields.push(forms[i][name].name.substring(6));
                    if (jax(forms[i][name]).data('path') !== null) {
                        path = jax(forms[i][name]).data('path');
                    }
                }
            }
        }

        if ((fields.length > 0) && (path != null)) {
            var values = {};
            for (var i = 0; i < fields.length; i++) {
                var json = jax.get(path + '/fields/json/' + jax('#id').val() + '/' + fields[i]);
                if ((json.values != undefined) && (json.values.constructor == Array) && (json.values.length > 0)) {
                    var fieldName = 'field_' + fields[i];
                    var fieldId   = fields[i];
                    if (fieldName.substr(-2) == '[]') {
                        fieldName = fieldName.substring(0, (fieldName.length - 2));
                        fieldId   = fieldId.substring(0, (fieldName.length - 2));
                    }
                    values[fieldName] = json.values;
                    phire.addField(fieldId, values);
                }
            }
        }
    }

    var editorLinks = jax('a.editor-link').toArray();
    if ((editorLinks != '') && (editorLinks.length > 0)) {
        var editor = jax(editorLinks[0]).data('editor');
        var path   = jax(editorLinks[0]).data('path');

        for (var i = 0; i < editorLinks.length; i++) {
            jax(editorLinks[i]).click(phire.changeEditor);
            var id = jax(editorLinks[i]).data('fid');
            var w = Math.round(jax('#field_' + id).width());
            var h = Math.round(jax('#field_' + id).height());
            phire.editorIds.push({ "id" : id, "width" : w, "height" : h });
        }

        if (editor != null) {
            var head   = document.getElementsByTagName('head')[0];
            var script = document.createElement("script");
            switch (editor) {
                case 'ckeditor':
                    script.src    = path + '/modules/phire/assets/js/ckeditor/ckeditor.js';
                    script.onload = script.onreadystatechange = function() {
                        if (typeof CKEDITOR != 'undefined') {
                            phire.loadEditor('ckeditor');
                        }
                    };
                    head.appendChild(script);
                    break;

                case 'tinymce':
                    script.src    = path + '/modules/phire/assets/js/tinymce/tinymce.min.js';
                    script.onload = script.onreadystatechange = function() {
                        if (typeof tinymce != 'undefined') {
                            phire.loadEditor('tinymce');
                        }
                    };
                    head.appendChild(script);
                    break;
            }
        }
    }
});
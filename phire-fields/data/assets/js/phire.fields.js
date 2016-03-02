/**
 * Fields Module Scripts for Phire CMS 2
 */

phire.validatorCount = 1;
phire.modelCount     = 1;
phire.curFields      = {};
phire.curFieldValue  = {};
phire.editorIds      = [];

phire.toggleEditor = function(sel) {
    if (jax(sel).val().indexOf('textarea') != -1) {
        jax('#editor').show();
    } else {
        jax('#editor').hide();
    }
};

phire.addValidator = function(vals) {
    if (vals == null) {
        vals = [{
            "validator" : '',
            "value"     : '',
            "message"   : ''
        }];
    }

    for (var i = 0; i < vals.length; i++) {
        phire.validatorCount++;

        // Add validator select field
        jax('#validator_1').clone({
            "name": 'validator_' + phire.validatorCount,
            "id": 'validator_' + phire.validatorCount
        }).appendTo(jax('#validator_1').parent());

        if ((vals[i].validator != '') && (vals[i].validator != null)) {
            jax('#validator_' + phire.validatorCount).val(vals[i].validator);
        }

        // Add validator value text field
        jax('#validator_value_1').clone({
            "name": 'validator_value_' + phire.validatorCount,
            "id": 'validator_value_' + phire.validatorCount
        }).appendTo(jax('#validator_value_1').parent());

        if ((vals[i].value != '') && (vals[i].value != null)) {
            jax('#validator_value_' + phire.validatorCount).val(vals[i].value);
        }

        // Add validator message text field
        jax('#validator_message_1').clone({
            "name": 'validator_message_' + phire.validatorCount,
            "id": 'validator_message_' + phire.validatorCount
        }).appendTo(jax('#validator_message_1').parent());

        if ((vals[i].message != '') && (vals[i].message != null)) {
            jax('#validator_message_' + phire.validatorCount).val(vals[i].message);
        }
    }

    return false;
};

phire.addModel = function(vals) {
    if (vals == null) {
        vals = [{
            "model"      : '',
            "type_field" : '',
            "type_value" : ''
        }];
    }
    for (var i = 0; i < vals.length; i++) {
        phire.modelCount++;

        // Add model select field
        jax('#model_1').clone({
            "name": 'model_' + phire.modelCount,
            "id": 'model_' + phire.modelCount
        }).appendTo(jax('#model_1').parent());

        if ((vals[i].model != '') && (vals[i].model != null)) {
            jax('#model_' + phire.modelCount).val(vals[i].model);
        }

        // Add model type text field
        jax('#model_type_1').clone({
            "name": 'model_type_' + phire.modelCount,
            "id": 'model_type_' + phire.modelCount
        }).appendTo(jax('#model_type_1').parent());

        phire.getModelTypes(jax('#model_' + phire.modelCount)[0]);

        if ((vals[i].type_field != '') && (vals[i].type_field != null) && (vals[i].type_value != '') && (vals[i].type_value != null)) {
            jax('#model_type_' + phire.modelCount).val(vals[i].type_field + '|' + vals[i].type_value);
        }
    }

    return false;
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
                    jax('#' + newName)[0].defaultValue = values[fieldName][j];
                }
            } else {
                if ((jax('#rm_field_file_' + fid)[0] != undefined) && (values[fieldName][j] != '')) {
                    var fileFieldSetParent = jax(jax('#rm_field_file_' + fid).parent()).parent();
                    var fileValues = {};
                    var filePath   = '#';

                    if (jax.cookie.load('phire') != '') {
                        var phireCookie = jax.cookie.load('phire');
                        filePath = phireCookie.base_path + phireCookie.content_path + '/files/' + values[fieldName][j];
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

phire.getModelTypes = function(sel) {
    if (jax(sel).val() != '----') {
        var id    = sel.id.substring(sel.id.lastIndexOf('_') + 1);
        var opts  = jax('#model_type_' + id + ' > option').toArray();
        var start = opts.length - 1;
        for (var i = start; i >= 0; i--) {
            jax(opts[i]).remove();
        }
        jax('#model_type_' + id).append('option', {"value" : '----'}, '----');

        if ((jax(sel).val() != '----') && (jax.cookie.load('phire') != '')) {

            var phireCookie = jax.cookie.load('phire');
            var path = phireCookie.base_path + phireCookie.app_uri;

            var json = jax.get(path + '/fields/json/' + encodeURIComponent(jax(sel).val()));
            if (json.length > 0) {
                for (var i = 0; i < json.length; i++) {
                    jax('#model_type_' + id).append('option', {
                        "value" : json[i].type_field + '|' + json[i].type_value
                    }, json[i].type_name);
                }
            }
        }
    }
};

phire.changeHistory = function(sel) {
    var ids     = sel.id.substring(sel.id.indexOf('_') + 1).split('_');
    var modelId = ids[0];
    var fieldId = ids[1];
    var marked  = jax('#' + sel.id + ' > option:selected').val();

    if ((phire.curFieldValue['field_' + fieldId] == undefined) && (jax('#field_' + fieldId)[0] != undefined)) {
        phire.curFieldValue['field_' + fieldId] = jax('#field_' + fieldId).val();
    }

    if ((marked != 0) && (jax.cookie.load('phire') != '')) {
        var phireCookie = jax.cookie.load('phire');
        var model       = encodeURIComponent(jax('#' + sel.id).data('model'));
        var j           = jax.json.parse(
            phireCookie.base_path + phireCookie.app_uri + '/fields/json/' + modelId + '/' + fieldId + '/' + marked + '?model=' + model
        );
        if (jax('#field_' + j.fieldId)[0] != undefined) {
            if (typeof CKEDITOR !== 'undefined') {
                if (CKEDITOR.instances['field_' + j.fieldId] != undefined) {
                    CKEDITOR.instances['field_' + j.fieldId].setData(j.value);
                }
            } else if (typeof tinymce !== 'undefined') {
                tinymce.editors['field_' + j.fieldId].setContent(j.value);
            }
            jax('#field_' + j.fieldId).val(j.value);
        }
    } else {
        if (jax('#field_' + fieldId)[0] != undefined) {
            if (typeof CKEDITOR !== 'undefined') {
                if (CKEDITOR.instances['field_' + fieldId] != undefined) {
                    CKEDITOR.instances['field_' + fieldId].setData(phire.curFieldValue['field_' + fieldId]);
                }
            } else if (typeof tinymce !== 'undefined') {
                tinymce.editors['field_' + fieldId].setContent(phire.curFieldValue['field_' + fieldId]);
            }
            jax('#field_' + fieldId).val(phire.curFieldValue['field_' + fieldId]);
        }
    }
};

if (phire.loadEditor == undefined) {
    phire.loadEditor = function (editor, id) {
        if (null != id) {
            var w = Math.round(jax('#field_' + id).width());
            var h = Math.round(jax('#field_' + id).height());
            phire.editorIds = [{"id": id, "width": w, "height": h}];
        }

        var sysPath = '';
        if (jax.cookie.load('phire') != '') {
            var phireCookie = jax.cookie.load('phire');
            sysPath = phireCookie.base_path + phireCookie.app_uri;
        }

        if (phire.editorIds.length > 0) {
            for (var i = 0; i < phire.editorIds.length; i++) {
                if (editor.indexOf('ckeditor') != -1) {
                    if (CKEDITOR.instances['field_' + phire.editorIds[i].id] == undefined) {
                        CKEDITOR.replace(
                            'field_' + phire.editorIds[i].id,
                            {
                                width                         : 'auto',
                                height                        : phire.editorIds[i].height,
                                allowedContent                : true,
                                filebrowserBrowseUrl          : sysPath + '/fields/browser?editor=ckeditor&type=file',
                                filebrowserImageBrowseUrl     : sysPath + '/fields/browser?editor=ckeditor&type=image',
                                filebrowserImageBrowseLinkUrl : sysPath + '/fields/browser?editor=ckeditor&type=file',
                                filebrowserWindowWidth        : '960',
                                filebrowserWindowHeight       : '720',
                                toolbarGroups                 : [
                                    { name: 'clipboard',   groups: [ 'clipboard', 'undo' ] },
                                    { name: 'editing',     groups: [ 'find', 'selection', 'spellchecker' ] },
                                    { name: 'links' },
                                    { name: 'insert' },
                                    { name: 'forms' },
                                    { name: 'tools' },
                                    { name: 'document',    groups: [ 'mode', 'document', 'doctools' ] },
                                    { name: 'others' },
                                    '/',
                                    { name: 'basicstyles', groups: [ 'basicstyles', 'cleanup' ] },
                                    { name: 'paragraph',   groups: [ 'list', 'indent', 'blocks', 'align', 'bidi' ] },
                                    { name: 'styles' },
                                    { name: 'colors' },
                                    { name: 'about' }
                                ]
                            }
                        );
                    }
                    var eid = phire.editorIds[i].id;
                    jax('#field_' + eid).keyup(function () {
                        CKEDITOR.instances['field_' + eid].setData(jax('#field_' + eid).val());
                    });
                } else if (editor.indexOf('tinymce') != -1) {
                    if (tinymce.editors['field_' + phire.editorIds[i].id] == undefined) {
                        tinymce.init(
                            {
                                selector: "textarea#field_" + phire.editorIds[i].id,
                                theme: "modern",
                                plugins: [
                                    "advlist autolink lists link image hr", "searchreplace wordcount code fullscreen",
                                    "table", "template paste textcolor"
                                ],
                                image_advtab: true,
                                toolbar1: "insertfile undo redo | styleselect | forecolor backcolor | bold italic underline strikethrough | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | table | link image",
                                width: 'auto',
                                height: phire.editorIds[i].height,
                                relative_urls: false,
                                convert_urls: 0,
                                remove_script_host: 0,
                                file_browser_callback: function (field_name, url, type, win) {
                                    tinymce.activeEditor.windowManager.open({
                                        title: "File Browser",
                                        url: sysPath + '/fields/browser?editor=tinymce&type=' + type,
                                        width: 960,
                                        height: 720
                                    }, {
                                        oninsert: function (url) {
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
                    jax('#field_' + eid).keyup(function () {
                        tinymce.editors['field_' + eid].setContent(jax('#field_' + eid).val());
                    });
                }
            }
        }
    };
}

phire.changeEditor = function() {
    var editor = jax(this).data('editor');
    var id     = jax(this).data('fid');
    if (this.innerHTML == 'Source') {
        this.innerHTML = 'Editor';
        if (typeof CKEDITOR !== 'undefined') {
            var content = CKEDITOR.instances['field_' + id].getData();
            CKEDITOR.instances['field_' + id].destroy();
        } else if (typeof tinymce !== 'undefined') {
            var content = tinymce.editors['field_' + id].getContent();
            tinymce.get('field_' + id).hide();
        }
        jax('#field_' + id).val(content);
        jax('#field_' + id).show();
    } else {
        this.innerHTML = 'Source';
        if (editor.indexOf('ckeditor') != -1) {
            phire.loadEditor(editor, id);
        } else if (editor.indexOf('tinymce') != -1) {
            phire.loadEditor(editor, id);
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
        var forms  = jax('form').toArray();
        var fields = [];
        var path   = null;
        var model  = null;

        for (var i = 0; i < forms.length; i++) {
            for (var name in forms[i]) {
                if ((forms[i][name] != undefined) && (forms[i][name] != null) && (forms[i][name].name != undefined) &&
                    (typeof forms[i][name].name.substring == 'function') && (forms[i][name].name.substring(0, 6) == 'field_') &&
                    (fields.indexOf(forms[i][name].name.substring(6)) == -1)) {
                    fields.push(forms[i][name].name.substring(6));
                    if ((jax(forms[i][name]).data('path') !== null) && (jax(forms[i][name]).data('model') !== null)) {
                        path  = jax(forms[i][name]).data('path');
                        model = jax(forms[i][name]).data('model');
                    }
                }
            }
        }

        if ((fields.length > 0) && (path != null) && (model != null)) {
            var values = {};
            for (var i = 0; i < fields.length; i++) {
                var json = jax.get(
                    path + '/fields/json/' + jax('#id').val() + '/' +
                    ((fields[i].substr(-2) == '[]') ? fields[i].substring(0, (fields[i].length - 2)) : fields[i]) +
                    '?model=' + encodeURIComponent(model)
                );
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
                case 'ckeditor-local':
                    script.src    = path + '/modules/phire/assets/js/ckeditor/ckeditor.js';
                    script.onload = script.onreadystatechange = function() {
                        if (typeof CKEDITOR != 'undefined') {
                            phire.loadEditor('ckeditor');
                        }
                    };
                    head.appendChild(script);
                    break;

                case 'ckeditor-remote':
                    script.src    = '//cdn.ckeditor.com/4.5.7/full/ckeditor.js';
                    script.onload = script.onreadystatechange = function() {
                        if (typeof CKEDITOR != 'undefined') {
                            phire.loadEditor('ckeditor');
                        }
                    };
                    head.appendChild(script);
                    break;

                case 'tinymce-local':
                    script.src    = path + '/modules/phire/assets/js/tinymce/tinymce.min.js';
                    script.onload = script.onreadystatechange = function() {
                        if (typeof tinymce != 'undefined') {
                            phire.loadEditor('tinymce');
                        }
                    };
                    head.appendChild(script);
                    break;

                case 'tinymce-remote':
                    script.src    = '//tinymce.cachefly.net/4.0/tinymce.min.js';
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

    if ((jax('#field-form')[0] != undefined) && (jax('#id').val() != 0)) {
        if (jax.cookie.load('phire') != '') {
            var phireCookie = jax.cookie.load('phire');
            var json = jax.get(phireCookie.base_path + phireCookie.app_uri + '/fields/json/0/' + jax('#id').val());
            if (json.validators.length > 0) {
                jax('#validator_1').val(json.validators[0].validator);
                if (json.validators[0].value != null) {
                    jax('#validator_value_1').val(json.validators[0].value);
                }
                if (json.validators[0].message != null) {
                    jax('#validator_message_1').val(json.validators[0].message);
                }
                json.validators.shift();
                if (json.validators.length > 0) {
                    phire.addValidator(json.validators);
                }
            }
            if (json.models.length > 0) {
                jax('#model_1').val(json.models[0].model);
                phire.getModelTypes(jax('#model_1')[0]);
                if ((json.models[0].type_field != null) && (json.models[0].type_value != null)) {
                    jax('#model_type_1').val(json.models[0].type_field + '|' + json.models[0].type_value);
                }
                json.models.shift();
                if (json.models.length > 0) {
                    phire.addModel(json.models);
                }
            }
        }
    }
});

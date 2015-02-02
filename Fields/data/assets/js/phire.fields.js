/**
 * Fields Module Scripts for Phire CMS 2
 */

phire.validatorCount = 1;
phire.modelCount     = 1;
phire.curFieldValue  = null;
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
                    jax('#model_type_' + cur + '_' + id).append('option', {"value" : json[i].type_field + '|' + json[i].type_value}, json[i].type_name);
                }
            }
        }
    }
};

phire.changeHistory = function(sel, path) {
    var ids = sel.id.substring(sel.id.indexOf('_') + 1).split('_');
    var modelId = ids[0];
    var fieldId = ids[1];
    var marked = jax('#' + sel.id + ' > option:selected').val();

    if ((phire.curFieldValue == null) && (jax('#field_' + fieldId)[0] != undefined)) {
        phire.curFieldValue = jax('#field_' + fieldId).val();
    }

    if (marked != 0) {
        var j = jax.json.parse(path + '/fields/json/' + modelId + '/' + fieldId + '/' + marked);
        if (jax('#field_' + j.fieldId)[0] != undefined) {
            /*
            if (typeof CKEDITOR !== 'undefined') {
                if (CKEDITOR.instances['field_' + j.fieldId] != undefined) {
                    CKEDITOR.instances['field_' + j.fieldId].setData(j.value);
                }
            } else if (typeof tinymce !== 'undefined') {
                tinymce.activeEditor.setContent(j.value);
            }
            */
            jax('#field_' + j.fieldId).val(j.value);
        }
    } else {
        if (jax('#field_' + fieldId)[0] != undefined) {
            /*
            if (typeof CKEDITOR !== 'undefined') {
                if (CKEDITOR.instances['field_' + fieldId] != undefined) {
                    CKEDITOR.instances['field_' + fieldId].setData(phire.curValue);
                }
            } else if (typeof tinymce !== 'undefined') {
                tinymce.activeEditor.setContent(phire.curValue);
            }
            */
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

    if (phire.editorIds.length > 0) {
        for (var i = 0; i < phire.editorIds.length; i++) {
            if (editor == 'ckeditor') {
                if (CKEDITOR.instances['field_' + phire.editorIds[i].id] == undefined) {
                    CKEDITOR.replace(
                        'field_' + phire.editorIds[i].id,
                        {
                            width          : phire.editorIds[i].width,
                            height         : phire.editorIds[i].height,
                            allowedContent : true
                        }
                    );
                }
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
                            width                 : phire.editorIds[i].width,
                            height                : phire.editorIds[i].height,
                            relative_urls         : false,
                            convert_urls          : 0,
                            remove_script_host    : 0
                        }
                    );
                } else {
                    tinymce.get('field_' + phire.editorIds[i].id).show();
                }
            }
        }
    }
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

    var editorLinks = jax('a.editor-link').toArray();
    if ((editorLinks != '') && (editorLinks.length > 0)) {
        var editor = jax(editorLinks[0]).data('editor');
        var path   = jax(editorLinks[0]).data('path');

        for (var i = 0; i < editorLinks.length; i++) {
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
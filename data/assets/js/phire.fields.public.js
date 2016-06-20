/**
 * Fields Module Public Scripts for Phire CMS 2
 */

jax(document).ready(function(){
    if (window.phire == undefined) {
        window.phire = {};
    }

    if (window.phire.editorIds == undefined) {
        window.phire.editorIds = [];
    }

    if (window.phire.loadEditor == undefined) {
        window.phire.loadEditor = function (editor, id) {
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
                                    toolbarGroups                 : [
                                        { name: 'basicstyles', groups: [ 'basicstyles', 'cleanup' ] },
                                        { name: 'paragraph',   groups: [ 'list', 'align' ] },
                                        { name: 'styles' },
                                        { name: 'colors' },
                                        { name: 'links' }
                                    ]
                                }
                            );
                        }
                        var eid = phire.editorIds[i].id;
                        jax('#field_' + eid).keyup(function () {
                            if ((typeof CKEDITOR !== 'undefined') && (CKEDITOR.instances['field_' + eid] != undefined)) {
                                CKEDITOR.instances['field_' + eid].setData(jax('#field_' + eid).val());
                            }
                        });
                    } else if (editor.indexOf('tinymce') != -1) {
                        if (tinymce.editors['field_' + phire.editorIds[i].id] == undefined) {
                            tinymce.init(
                                {
                                    menubar: false,
                                    selector: "textarea#field_" + phire.editorIds[i].id,
                                    theme: "modern",
                                    plugins: [
                                        "advlist autolink lists link image hr", "searchreplace wordcount code fullscreen",
                                        "table", "template paste textcolor"
                                    ],
                                    image_advtab: true,
                                    toolbar1: "undo redo | styleselect | bold italic underline strikethrough | fontselect | fontsizeselect | forecolor backcolor | alignleft aligncenter alignright alignjustify | bullist numlist | link",
                                    width: 'auto',
                                    height: phire.editorIds[i].height,
                                    relative_urls: false,
                                    convert_urls: 0,
                                    remove_script_host: 0
                                }
                            );
                        } else {
                            tinymce.get('field_' + phire.editorIds[i].id).show();
                        }
                        var eid = phire.editorIds[i].id;
                        jax('#field_' + eid).keyup(function () {
                            if ((typeof tinymce !== 'undefined') && (tinymce.editors['field_' + eid] != undefined)) {
                                tinymce.editors['field_' + eid].setContent(jax('#field_' + eid).val());
                            }
                        });
                    }
                }
            }
        };
    }

    if (window.phire.changeEditor == undefined) {
        window.phire.changeEditor = function () {
            return false;
        };
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
});
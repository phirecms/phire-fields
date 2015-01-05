/**
 * Fields Module Scripts for Phire CMS 2
 */

phire.validatorCount = 1;
phire.modelCount = 1;

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

phire.getModelTypes = function(sel, path) {
    if (jax(sel).val() != '----') {
        var id    = sel.id.substring(sel.id.lastIndexOf('_') + 1);
        var opts  = jax('#model_type_new_' + id + ' > option').toArray();
        var start = opts.length - 1;
        for (var i = start; i >= 0; i--) {
            jax(opts[i]).remove();
        }
        jax('#model_type_new_' + id).append('option', {"value" : '----'}, '----');

        if (jax(sel).val() != '----') {
            var json = jax.get(path + '/fields/json/' + jax(sel).val());
            if (json.length > 0) {
                for (var i = 0; i < json.length; i++) {
                    jax('#model_type_new_' + id).append('option', {"value" : json[i].type_field + '|' + json[i].type_value}, json[i].type_name);
                }
            }
        }
    }
};

jax(document).ready(function(){
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
});
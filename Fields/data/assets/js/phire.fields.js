/**
 * Fields Module Scripts for Phire CMS 2
 */

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
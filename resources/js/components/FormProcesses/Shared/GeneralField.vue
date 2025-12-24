<template>
    <div class="accordion-item">
        <h2 class="accordion-header" id="general">
            <button class="accordion-button" type="button" data-bs-toggle="collapse"
                    data-bs-target="#collapseGeneral" aria-expanded="true"
                    aria-controls="collapseGeneral">
                {{ trans.general }}
            </button>
        </h2>
        <div id="collapseGeneral" class="accordion-collapse collapse show"
             aria-labelledby="general">
            <div class="accordion-body text-body">
                <general-form-field :trans="trans" :form="form" :errors="errors"
                                    toggle_enabled="edit_toggle_process"></general-form-field>
            </div>
        </div>
    </div>
</template>

<script>
import GeneralFormField from "./GeneralFormField.vue";

export default {
    name: "GeneralField",
    components: {GeneralFormField},
    props: ['trans', 'form', 'errors'],
}
</script>

<template>
    <form-saved></form-saved>

    <div class="accordion custom-accordionwithicon-plus">
        <form-information></form-information>
        <form-structure></form-structure>
        <form-integration></form-integration>
    </div>
</template>

<script>
import FormInformation from "./FormInformation.vue";
import FormStructure from "./FormStructure.vue";
import FormSaved from "./FormSaved.vue";
import FormIntegration from "./FormIntegration.vue";

export default {
    name: "FormElement",
    components: {
        FormInformation,
        FormStructure,
        FormSaved,
        FormIntegration,
    }
}
</script>

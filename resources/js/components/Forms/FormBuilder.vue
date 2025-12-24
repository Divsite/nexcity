<template>
    <div class="row">
        <div class="col-xl-4 mb-3">
            <form-element></form-element>
        </div>

        <div class="col-xl-8">
            <form-preview></form-preview>
        </div>
    </div>
</template>

<script>
import FormElement from "./Elements/FormElement.vue";
import FormPreview from "./Views/FormPreview.vue";

export default {
    name: "FormBuilder",
    components: {
        FormElement,
        FormPreview,
    },
}
</script>

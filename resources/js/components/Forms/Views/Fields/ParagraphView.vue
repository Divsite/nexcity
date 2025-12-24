<template>
    <div class="col-md-12">
        <div class="mb-3">
            <p>{{ field.text }}</p>
        </div>
    </div>
</template>

<script>
export default {
    name: "ParagraphView",
    props: {
        field: {
            type: Object,
            required: false,
        }
    }
}
</script>

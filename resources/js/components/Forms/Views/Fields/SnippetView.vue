<template>
    <div class="col-md-12">
        <div class="mb-3">
            <div v-html="field.content"></div>
        </div>
    </div>
</template>

<script>
export default {
    name: "SnippetView",
    props: {
        field: {
            type: Object,
            required: false,
        }
    }
}
</script>

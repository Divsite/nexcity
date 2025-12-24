<template>
    <div class="col-md-12">
        <div class="mb-3">
            <h1 v-if="field.tag === 'h1'">{{ field.text }}</h1>
            <h2 v-if="field.tag === 'h2'">{{ field.text }}</h2>
            <h3 v-if="field.tag === 'h3'">{{ field.text }}</h3>
            <h4 v-if="field.tag === 'h4'">{{ field.text }}</h4>
            <h5 v-if="field.tag === 'h5'">{{ field.text }}</h5>
            <h6 v-if="field.tag === 'h6'">{{ field.text }}</h6>
        </div>
    </div>
</template>

<script>
export default {
    name: "HeadingView",
    props: {
        field: {
            type: Object,
            required: false,
        }
    }
}
</script>

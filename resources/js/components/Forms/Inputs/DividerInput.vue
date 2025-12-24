<template>
    <div class="col-md-12">
        <div class="mb-3">
            <div :style="dividerStyle"></div>
        </div>
    </div>
</template>

<script>
export default {
    name: "DividerInput",
    props: {
        field: {
            type: Object,
            required: false,
        },
    },
    computed: {
        dividerStyle() {
            return {
                borderTop: `${this.field.thickness} solid ${this.field.color}`,
            };
        }
    }
}
</script>

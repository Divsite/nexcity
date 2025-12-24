<template>
    <span class="text-muted">
        <span>({{ trans.references }}: </span>
        <a href="https://regexr.com" target="_blank"
           class="link-info link-offset-2 text-decoration-underline link-underline-opacity-25 link-underline-opacity-100-hover">RegExr</a>
        <span>, </span>
        <a href="https://regexlearn.com" target="_blank"
           class="link-info link-offset-2 text-decoration-underline link-underline-opacity-25 link-underline-opacity-100-hover">RegexLearn</a>
        <span>)</span>
    </span>
</template>

<script>
export default {
    name: "RegexLink",
    data() {
        return {
            trans: {
                references: trans.references,
            }
        }
    }
}
</script>

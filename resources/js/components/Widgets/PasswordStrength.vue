<template>
    <div class="row icon-demo-content text-black fs-12">
        <div class="col-md-12">
            <i class="fs-20 fw-bolder me-2" :class=minClassObject></i>
            <span class="align-middle">{{ trans.at_least_min_characters }}</span>
        </div>
        <div class="col-md-12" v-if="rules.mixed_case">
            <i class="fs-20 fw-bolder me-2" :class="mixedCaseClassObject"></i>
            <span class="align-middle">{{ trans.one_lowercase_letter_and_one_uppercase_letter }}</span>
        </div>
        <div class="col-md-12" v-if="rules.letter">
            <i class="fs-20 fw-bolder me-2" :class="letterClassObject"></i>
            <span class="align-middle">{{ trans.one_letter }}</span>
        </div>
        <div class="col-md-12" v-if="rules.number">
            <i class="fs-20 fw-bolder me-2" :class="numberClassObject"></i>
            <span class="align-middle">{{ trans.one_number }}</span>
        </div>
        <div class="col-md-12" v-if="rules.symbol">
            <i class="fs-20 fw-bolder me-2" :class="symbolClassObject"></i>
            <span class="align-middle">{{ trans.one_symbol }}</span>
        </div>
    </div>
</template>

<script>
export default {
    name: "PasswordStrength",
    props: {
        value: String,
        rules: Object,
        trans: Object,
    },
    data() {
        return {
            mixedCase: false,
            letters: false,
            numbers: false,
            symbols: false,
        };
    },
    computed: {
        minClassObject() {
            return {
                'ri-check-fill text-success': this.value.length >= this.rules.min,
                'ri-close-fill text-danger': this.value.length < this.rules.min,
            };
        },
        mixedCaseClassObject() {
            return {
                'ri-check-fill text-success': this.mixedCase,
                'ri-close-fill text-danger': !this.mixedCase,
            };
        },
        letterClassObject() {
            return {
                'ri-check-fill text-success': this.letters,
                'ri-close-fill text-danger': !this.letters,
            };
        },
        numberClassObject() {
            return {
                'ri-check-fill text-success': this.numbers,
                'ri-close-fill text-danger': !this.numbers,
            };
        },
        symbolClassObject() {
            return {
                'ri-check-fill text-success': this.symbols,
                'ri-close-fill text-danger': !this.symbols,
            };
        }
    },
    watch: {
        value(newValue) {
            if (this.rules.mixed_case) {
                this.mixedCase = /(\p{Ll}+.*\p{Lu})|(\p{Lu}+.*\p{Ll})/u.test(newValue);
            }

            if (this.rules.letter) {
                this.letters = /[a-zA-Z]/u.test(newValue);
            }

            if (this.rules.number) {
                this.numbers = /\d/.test(newValue);
            }

            if (this.rules.symbol) {
                this.symbols = /\p{Z}|\p{S}|\p{P}/u.test(newValue);
            }
        }
    }
}
</script>

<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

type Props = {
    className?: HTMLAttributes['class'];
};

defineProps<Props>();
</script>

<template>
    <img
        src="/images/logo-icon.png"
        alt="Arca Barber"
        width="192"
        height="192"
        class="rounded-md object-cover"
        :class="className"
        v-bind="$attrs"
    />
</template>

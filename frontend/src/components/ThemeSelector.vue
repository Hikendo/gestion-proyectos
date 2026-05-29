<script setup lang="ts">
import { computed } from 'vue';
import { storeToRefs } from 'pinia';
import { useThemeStore } from '@/store/useThemeStore';
import { themeList } from '@/constants/themes';

const store = useThemeStore();
const { currentKey } = storeToRefs(store);

// VSelect items: value = theme key, title = label, with swatch color attached
const selectItems = computed(() =>
  themeList.map((t) => ({
    value: t.key,
    title: t.label,
    subtitle: t.description,
    color: t.preview,
  })),
);

// Safe lookup for the selection slot — never undefined
const selectedItem = computed(
  () => selectItems.value.find((i) => i.value === currentKey.value) ?? selectItems.value[0],
);

function onSelect(key: string) {
  store.setTheme(key);
}
</script>

<template>
  <div class="pa-4">
    <VSelect
      :model-value="currentKey"
      :items="selectItems"
      item-value="value"
      item-title="title"
      label="Tema de la interfaz"
      variant="outlined"
      density="comfortable"
      prepend-inner-icon="mdi-palette-outline"
      hide-details
      @update:model-value="onSelect"
    >
      <!-- Custom item: swatch dot + label + description -->
      <template #item="{ item, props: itemProps }">
        <VListItem v-bind="itemProps" :title="undefined">
          <template #prepend>
            <span
              class="theme-swatch"
              :style="{ background: item.raw.color }"
            />
          </template>
          <VListItemTitle class="font-weight-medium">{{ item.raw.title }}</VListItemTitle>
          <VListItemSubtitle class="text-caption">{{ item.raw.subtitle }}</VListItemSubtitle>
          <template #append>
            <VIcon
              v-if="currentKey === item.raw.value"
              icon="mdi-check-circle"
              size="16"
              color="primary"
            />
          </template>
        </VListItem>
      </template>

      <!-- Selected value display: swatch + label -->
      <template #selection>
        <div class="d-flex align-center gap-2">
          <span
            class="theme-swatch"
            :style="{ background: selectedItem.color }"
          />
          <span>{{ selectedItem.title }}</span>
        </div>
      </template>
    </VSelect>

    <!-- Live preview strip -->
    <div class="preview-strip mt-3">
      <span
        v-for="t in themeList"
        :key="t.key"
        class="preview-dot"
        :class="{ active: currentKey === t.key }"
        :title="t.label"
        :style="{ background: t.preview }"
        @click="onSelect(t.key)"
      />
    </div>
  </div>
</template>

<style scoped>
.theme-swatch {
  display: inline-block;
  width: 12px;
  height: 12px;
  border-radius: 50%;
  flex-shrink: 0;
  box-shadow: 0 0 0 2px rgba(0, 0, 0, 0.12);
}

.preview-strip {
  display: flex;
  gap: 10px;
  justify-content: center;
}

.preview-dot {
  width: 28px;
  height: 28px;
  border-radius: 50%;
  cursor: pointer;
  transition: transform 0.15s ease, box-shadow 0.15s ease;
  box-shadow: 0 0 0 2px rgba(0, 0, 0, 0.12);
}

.preview-dot:hover {
  transform: scale(1.15);
}

.preview-dot.active {
  transform: scale(1.2);
  box-shadow: 0 0 0 3px currentColor, 0 0 0 5px rgba(0, 0, 0, 0.15);
}
</style>

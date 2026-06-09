/**
 * Custom Remix Icon set for Vuetify 3.
 * 
 * Maps icon names like "ri-home-line" to Remix Icon CSS classes.
 * 
 * Usage: <VIcon icon="ri-home-line" />
 */
import { h } from 'vue';
import type { IconSet, IconProps } from 'vuetify';

const remix: IconSet = {
  component: (props: IconProps) => {
    const iconName = (props.icon as string).replace('ri-', '');
    
    return h(props.tag || 'i', {
      class: ['ri-' + iconName, props.class],
      style: props.style,
      onClick: props.onClick,
      ...props,
    });
  },
};

export { remix };
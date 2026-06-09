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
    // Destructure to avoid spreading icon (and other Vuetify internal props) onto the DOM element.
    // The spread ...props would overwrite class/style/onClick defined above.
    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    const { icon, ...domProps } = props as any;

    return h(props.tag || 'i', {
      ...domProps,
      class: ['ri-' + iconName, props.class],
    });
  },
};

export { remix };
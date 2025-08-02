/**
 * File editor/index.js.
 */
import { registerPlugin } from '@wordpress/plugins';
import Sidebar from "./sidebar.js";

registerPlugin('interactive-lesson-sidebar', {
    render: Sidebar,
    icon: 'admin-plugins',
});

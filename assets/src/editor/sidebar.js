/**
 * Meta Options build.
 */
import { PluginSidebar } from "@wordpress/edit-post";
import { __ } from "@wordpress/i18n";
import { Component, Fragment } from "@wordpress/element";
import { PanelBody, TextControl, SelectControl } from "@wordpress/components";
import { withSelect, withDispatch } from "@wordpress/data";
import { compose } from "@wordpress/compose";

class Sidebar extends Component {
    constructor() {
        super(...arguments);
        this.state = {};
    }

    render() {
        const { meta, setMetaFieldValue, products } = this.props;

        // Prepare product options for SelectControl
        const productOptions = [
            { label: __('Select a Item', 'interactive-lesson'), value: '' },
            ...products.map((product) => ({
                label: product.title.rendered,
                value: product.id,
            })),
        ];

        return (
            <Fragment>
                <PluginSidebar name="interactive-lesson-sidebar" title={__('Interactive Lesson', 'interactive-lesson')}>
                    <PanelBody title={__('Review Details', 'interactive-lesson')}>
                        <SelectControl
                            label={__('Review Item', 'interactive-lesson')}
                            value={meta.review_item || ''}
                            options={productOptions}
                            onChange={(value) => setMetaFieldValue(value, 'review_item')}
                        />
                        <SelectControl
                            label={__('Rating (1-5)', 'interactive-lesson')}
                            value={meta.reviewer_rating || ''}
                            options={[
                                { label: __('Select Rating', 'interactive-lesson'), value: '' },
                                { label: '1', value: '1' },
                                { label: '2', value: '2' },
                                { label: '3', value: '3' },
                                { label: '4', value: '4' },
                                { label: '5', value: '5' },
                            ]}
                            onChange={(value) => setMetaFieldValue(value, 'reviewer_rating')}
                        />
                        <TextControl
                            label={__("Reviewer's Name", "interactive-lesson")}
                            value={meta.reviewer_name || ''}
                            onChange={(value) => setMetaFieldValue(value, 'reviewer_name')}
                        />
                    </PanelBody>
                </PluginSidebar>
            </Fragment>
        );
    }
}

export default compose(
    withSelect((select) => {
        // Retrieve the current post's saved meta
        const postMeta = select("core/editor").getEditedPostAttribute("meta");
        const oldPostMeta = select("core/editor").getCurrentPostAttribute("meta");
        // Fetch all WooCommerce products
        const products = select('core').getEntityRecords('postType', 'post', {
            per_page: -1, // Retrieve all products
            status: 'publish', // Only published products
        }) || [];

        return {
            meta: { ...oldPostMeta, ...postMeta },
            oldMeta: oldPostMeta,
            products,
        };
    }),
    withDispatch((dispatch) => ({
        setMetaFieldValue: (value, field) =>
            dispatch("core/editor").editPost({ meta: { [field]: value } }),
    }))
)(Sidebar);
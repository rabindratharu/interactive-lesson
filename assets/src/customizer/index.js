/*CSS*/
import './index.scss';

const { Component } = wp.element;
const { TextControl, Button, BaseControl } = wp.components;
const { __ } = wp.i18n;

class InteractiveLessonTextControl extends Component {
    constructor(props) {
        super(props);
        this.state = {
            value: props.value || props.defaultValue || ''
        };
    }

    handleReset = () => {
        this.setState({ value: this.props.defaultValue || '' });
        wp.customize(this.props.setting).set(this.props.defaultValue || '');
    };

    render() {
        const { label, description, defaultValue } = this.props;
        const { value } = this.state;
        const showReset = value !== defaultValue;

        return (
            <BaseControl
                label={label}
                help={description}
                className="interactive-lesson-control"
            >
                <div className="interactive-lesson-control__wrapper">
                    <TextControl
                        value={value}
                        onChange={(newValue) => {
                            this.setState({ value: newValue });
                            wp.customize(this.props.setting).set(newValue);
                        }}
                    />
                    {showReset && (
                        <Button
                            className="interactive-lesson-control__reset"
                            isSmall
                            isSecondary
                            onClick={this.handleReset}
                        >
                            {__('Reset', 'interactive-lesson')}
                        </Button>
                    )}
                </div>
            </BaseControl>
        );
    }
}

// Initialize when Customizer is ready
wp.customize.bind('ready', () => {
    document.querySelectorAll('[id^="interactive-lesson-text-control-"]').forEach(container => {
        try {
            const props = JSON.parse(container.dataset.props);

            // Initial render
            wp.element.render(
                <InteractiveLessonTextControl {...props} />,
                container
            );

            // Update on external changes
            wp.customize(props.setting, (setting) => {
                setting.bind((newValue) => {
                    wp.element.render(
                        <InteractiveLessonTextControl {...props} value={newValue} />,
                        container
                    );
                });
            });
        } catch (e) {
            console.error('Error parsing control props:', e);
        }
    });
});
<?php if (!$noCustomPresets) { ?>
    <h3>Settings Presets</h3>
    <div class="custom-presets">
        <script type="text/javascript">
            (function() {
                'use strict';
                const ADD_FINISH_EVENT = 'custom--finish';
                const DEFAULT_PRESETS = {}; //TODO do we want some?

                const ROCKETMAP_LOAD_SETTINGS = (settings) => {
                    //this is what rocket map does (finally) when you import new preset
                    //BTW for gods-know-why reason upload is calling JSON.parse(JSON.parse(argument[0]))...
                    upload(settings);
                };

                function getSinglePresetElement(presetName) {
                    return `
                        <li class="custom-presets__container-item">
                            <button class="custom-presets__button custom-presets__button--preset" data-preset-name="${presetName}" title="${presetName}">${presetName}</button>
                            <button class="custom-presets__button custom-presets__button--delete-preset" data-preset-name="${presetName}"><i class="fa fa-trash-o" aria-hidden="true"></i></button>
                        </li>
                    `;
                }

                function getPresetAdder() {
                    const template = $(`<div class="custom-presets__adder">
                        <input type="file" hidden="hidden"/>
                        <input type="text" name="new-preset-name"/>
                        <div class="custom-presets__adder-buttons">
                            <div class="expanding custom-presets__adder-name-error">Name is already taken.</div>
                            <button class="expanding hide-on-error" data-current="true">Save current</button>
                            <button class="expanding hide-on-error" data-current="false">Import new</button>
                            <button><i class="fa fa-times"></i></button>
                        </div>
                    </div>`);
                    const nameInput = template.find('[type=text]');
                    const errorSpace = template.find('.custom-presets__adder-name-error').hide();
                    const buttons = template.find('.custom-presets__adder-buttons .hide-on-error').prop('disabled', true);

                    let tempPresetName;

                    template.on('change', '[type=file]', function(ev) {
                        const reader = new FileReader();
                        reader.onload = () => {
                            addPreset(tempPresetName, reader.result);
                            tempPresetName = null;
                            ev.target.value = '';
                            window.renderPresets();
                        };
                        reader.readAsText(ev.target.files[0]);
                    });
                    template.on('click', 'button', function(ev) {
                        const name = nameInput.val();
                        const isCloseButton = ev.target.dataset.current === undefined;
                        if (name && window.availablePresets[name] === undefined && !isCloseButton) {
                            tempPresetName = name;
                            if (ev.target.dataset.current === 'false') {
                                template.find('[type=file]').click();
                            } else {
                                addPreset(tempPresetName, JSON.stringify(JSON.stringify(localStorage)));
                                renderPresets();
                            }
                        }
                        template.trigger(ADD_FINISH_EVENT);
                    });
                    template.on('keypress input change', '[type=text]', function() {
                        const currentName = this.value;

                        buttons.prop('disabled', !currentName);

                        if (window.availablePresets[currentName] !== undefined) {
                            errorSpace.show();
                            buttons.hide();
                        } else {
                            errorSpace.hide();
                            buttons.show();
                        }
                    });
                    template.on(ADD_FINISH_EVENT, function() {
                        nameInput.val('');
                        errorSpace.hide();
                        buttons.show().prop('disabled', true);
                    });

                    return template;
                }

                function setupPresets(container) {
                    container.append(`<ul class="custom-presets__container"></ul>`);

                    const presetAdder = getPresetAdder().hide();
                    const getAddButton = () => container.find('.custom-presets__button--new');

                    container.on('click', '.custom-presets__button--new', function() {
                        getAddButton().hide();
                        presetAdder.show();
                    });
                    presetAdder.on(ADD_FINISH_EVENT, function () {
                        getAddButton().show();
                        presetAdder.hide();
                    });
                    container.on('click', '.custom-presets__button--preset', function() {
                        const presetName = $(this).closest('.custom-presets__button--preset').data('presetName');
                        const preset = window.availablePresets[presetName];
                        ROCKETMAP_LOAD_SETTINGS(preset);
                    });
                    container.on('click', '.custom-presets__button--delete-preset', function() {
                        const presetName = $(this).closest('.custom-presets__button--delete-preset').data('presetName');
                        removePreset(presetName);
                        window.renderPresets();
                    });

                    container.append(presetAdder);
                }

                function getPresetsRenderer(element) {
                    const templateGetter = () => {
                        const presetNames = window.availablePresets ? Object.keys(window.availablePresets) : [];
                        return `
                            ${presetNames.map(getSinglePresetElement).join('\n')}
                            <li class="custom-presets__container-item">
                                <button class="custom-presets__button custom-presets__button--new">+</button>
                            </li>
                        `;
                    };
                    const container = element.find('.custom-presets__container');
                    const adder = element.find('.custom-presets__adder');

                    return () => {
                        container.empty().html(templateGetter());
                        if (adder.is(':visible')) {
                            container.find('.custom-presets__button--new').hide();
                        }
                    };
                }

                function loadPresets() {
                    const raw = localStorage.getItem('customPresets');
                    if (raw) {
                        window.availablePresets = JSON.parse(raw);
                    } else {
                        window.availablePresets = DEFAULT_PRESETS;
                        localStorage.setItem('customPresets', JSON.stringify(window.availablePresets));
                    }
                }

                function addPreset(name, value) {
                    let isValueValid = false;
                    let parsed;

                    try { //simple check if we're getting a pokemap json
                        parsed = JSON.parse(JSON.parse(value)); //because rocketmap stringify twice...
                        isValueValid = true;
                    } catch(e) {
                        alert('Invalid file');
                    }

                    if (isValueValid) {
                        //we need to remove presets as they also get exported...
                        const safe = Object.keys(StoreOptions).reduce(
                            (result, key) => {
                                if (!result.hasOwnProperty(key)) {
                                    const option = StoreOptions[key];
                                    result[key] = option.type.stringify(option.default);
                                }
                                return result;
                            },
                            Object.assign({}, parsed)
                        );
                        delete safe.customPresets;
                        window.availablePresets[ name ] = JSON.stringify(JSON.stringify(safe));
                        localStorage.setItem('customPresets', JSON.stringify(window.availablePresets));
                    }
                }

                function removePreset(name) {
                    delete window.availablePresets[name];
                    localStorage.setItem('customPresets', JSON.stringify(window.availablePresets));
                }

                function sortPresets(comparer) {
                    const initial = JSON.parse(localStorage.getItem('customPresets'));
                    const sorted = Object.keys(initial).sort(comparer).reduce(
                        (result, currentKey) => {
                            result[currentKey] = initial[currentKey];
                            return result;
                        },
                        {}
                    );
                    localStorage.setItem('customPresets', JSON.stringify(sorted));
                    loadPresets();
                    window.renderPresets();
                }

                window.addEventListener('load', function addPresets() {
                    loadPresets();

                    const customPresets = $('.custom-presets');
                    setupPresets(customPresets);
                    window.renderPresets = getPresetsRenderer(customPresets);
                    window.sortPresets = sortPresets;
                    window.renderPresets();
                    customPresets.on('click', ev => ev.stopPropagation && ev.stopPropagation());

                    console.log('Custom script - presets');
                });
            })();
        </script>
        <style type="text/css" scoped>
            .custom-presets {
                border-top: 2px solid black;
                margin-top: 10px;
            }
            .custom-presets__header {
                margin: .5em 0;
                text-align: center;
            }
            .custom-presets__container {
                margin: .5em 0;
                padding: 0 .5em;
            }
            .custom-presets__container-item {
                margin: 0 0 5px 0;
                padding: 0;
                display: flex;
                flex-flow: row nowrap;
            }
            .custom-presets__container-item .custom-presets__button--preset,
            .custom-presets__container-item .custom-presets__button--new {
                flex: 1 1 auto;
                overflow: hidden;
                text-overflow: ellipsis;
            }
            .custom-presets__container-item .custom-presets__button--delete-preset {
                flex: 0 0 40px;
                margin-left: 5px;
                padding: 0;
            }
            .custom-presets__button {
                margin: 0;
            }
            .custom-presets__adder {
                margin: .5em 0;
                padding: 0 .5em;
            }
            .custom-presets__adder > input[type=text] {
                width: 100%;
                color: black;
            }
            .custom-presets__adder-buttons {
                display: flex;
                flex-flow: row nowrap;
                align-items: center;
                margin-top: 5px;
            }
            .custom-presets__adder-buttons > * {
                margin: 0;
                padding: 0 4px;
            }
            .custom-presets__adder-buttons > *.expanding {
                flex: 1 1 0;
            }
            .custom-presets__adder-buttons > *:not(:first-of-type) {
                margin-left: 5px;
            }
            .custom-presets__adder-name-error {
                color: darkred;
                text-align: center;
            }
        </style>
    </div>
<?php } ?>
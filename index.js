/**
 * StarFish - WordPress 配置框架 JavaScript
 * 使用原生 JavaScript，不依赖 jQuery
 *
 * @author vthemecn <mail@vtheme.cn>
 * @link https://vtheme.cn
 */

(function() {
    'use strict';
    
    // 等待 DOM 加载完成
    document.addEventListener('DOMContentLoaded', function() {
        initDependencies();
        initColorPickers();
        initUploadFields();
        initImageFields();
        initGalleryFields();
        initGroupFields();
        initSorterFields();
        initSliders();
        initBackupFields();
        initRemoveButtons();
    });
    
    /**
     * 初始化字段依赖
     */
    function initDependencies() {
        var dependentFields = document.querySelectorAll('.starfish-has-dependency');
        
        // 初始检查所有依赖字段
        dependentFields.forEach(function(field) {
            checkDependency(field);
        });
        
        // 监听依赖源字段的变化
        var allFields = document.querySelectorAll('.starfish-field');
        allFields.forEach(function(field) {
            var fieldId = field.getAttribute('data-field-id');
            if (!fieldId) return;
            
            var inputs = field.querySelectorAll('input, select, textarea');
            inputs.forEach(function(input) {
                input.addEventListener('change', function() {
                    updateDependentFields(fieldId);
                });
                
                // 对于文本输入，也监听 input 事件
                if (input.type === 'text' || input.type === 'textarea' || input.tagName === 'TEXTAREA') {
                    input.addEventListener('input', function() {
                        updateDependentFields(fieldId);
                    });
                }
            });
        });
    }
    
    /**
     * 更新依赖字段显示状态
     */
    function updateDependentFields(sourceFieldId) {
        var dependentFields = document.querySelectorAll('.starfish-has-dependency');
        
        dependentFields.forEach(function(field) {
            var dependencyField = field.getAttribute('data-dependency-field');
            if (dependencyField === sourceFieldId) {
                checkDependency(field);
            }
        });
    }
    
    /**
     * 检查单个依赖条件
     */
    function checkDependency(field) {
        var dependencyFieldId = field.getAttribute('data-dependency-field');
        var dependencyValue = field.getAttribute('data-dependency-value');
        
        if (!dependencyFieldId || !dependencyValue) {
            return;
        }
        
        // 查找依赖源字段
        var sourceField = document.querySelector('[data-field-id="' + dependencyFieldId + '"]');
        if (!sourceField) {
            return;
        }
        
        // 获取源字段的值
        var sourceValue = getSourceFieldValue(sourceField);
        
        // 比较值并显示/隐藏字段
        if (sourceValue === dependencyValue) {
            field.classList.remove('starfish-hidden');
        } else {
            field.classList.add('starfish-hidden');
        }
    }
    
    /**
     * 获取源字段的值
     */
    function getSourceFieldValue(field) {
        var checkbox = field.querySelector('input[type="checkbox"]');
        var radio = field.querySelector('input[type="radio"]:checked');
        var select = field.querySelector('select');
        var textInput = field.querySelector('input[type="text"], input[type="number"], textarea');
        
        if (checkbox) {
            return checkbox.checked ? checkbox.value : '';
        } else if (radio) {
            return radio.value;
        } else if (select) {
            return select.value;
        } else if (textInput) {
            return textInput.value;
        }
        
        return '';
    }
    
    /**
     * 绑定图片上传按钮事件
     */
    function bindImageUploadButton(button) {
        console.log('Binding image upload button:', button);
        
        // 先移除可能存在的旧事件监听器
        var newButton = button.cloneNode(true);
        if (button.parentNode) {
            button.parentNode.replaceChild(newButton, button);
            button = newButton;
        }
        
        button.addEventListener('click', function(e) {
            console.log('Image button clicked!', this);
            e.preventDefault();
            
            var wrapper = this.closest('.starfish-image-wrapper');
            if (!wrapper) {
                console.log('Wrapper not found');
                return;
            }
            console.log('Wrapper found:', wrapper);
            
            var urlInput = wrapper.querySelector('.starfish-image-url');
            var previewDiv = wrapper.querySelector('.starfish-image-preview');
            
            if (!urlInput || !previewDiv) {
                console.log('urlInput or previewDiv not found');
                console.log('urlInput:', urlInput);
                console.log('previewDiv:', previewDiv);
                return;
            }
            console.log('urlInput and previewDiv found');
            
            console.log('wp.media available:', typeof wp !== 'undefined' && wp.media);
            
            if (typeof wp !== 'undefined' && wp.media) {
                console.log('Creating media frame...');
                var imageFrame = wp.media({
                    title: starfishData.strings.selectImage || '选择图片',
                    button: {
                        text: starfishData.strings.useThisImage || '使用此图片'
                    },
                    multiple: false,
                    library: {
                        type: 'image'
                    }
                });
                
                console.log('Media frame created:', imageFrame);
                
                imageFrame.on('select', function() {
                    console.log('Image selected!');
                    var attachment = imageFrame.state().get('selection').first().toJSON();
                    urlInput.value = attachment.url;
                    
                    // 更新预览
                    previewDiv.innerHTML = '<img src="' + attachment.url + '" style="max-width: 150px; height: auto;">';
                    
                    // 如果删除按钮不存在，创建它
                    var removeBtn = wrapper.querySelector('.starfish-remove-button');
                    if (!removeBtn) {
                        removeBtn = document.createElement('button');
                        removeBtn.type = 'button';
                        removeBtn.className = 'button starfish-remove-button';
                        removeBtn.textContent = starfishData.strings.remove || '移除';
                        
                        // 插入到图片上传按钮后面
                        button.parentNode.insertBefore(removeBtn, button.nextSibling);
                        
                        // 绑定删除事件
                        bindRemoveButton(removeBtn);
                        console.log('Remove button created and bound');
                    }
                    
                    // 触发 change 事件
                    var event = new Event('change', { bubbles: true });
                    urlInput.dispatchEvent(event);
                });
                
                console.log('Opening media frame...');
                imageFrame.open();
                console.log('Media frame opened');
            } else {
                console.log('wp.media not available!');
            }
        });
        
        console.log('Event listener attached to:', button);
    }
    
    /**
     * 绑定文件上传按钮事件
     */
    function bindFileUploadButton(button) {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            var wrapper = this.closest('.starfish-upload-wrapper');
            if (!wrapper) return;
            
            var urlInput = wrapper.querySelector('.starfish-upload-url');
            if (!urlInput) return;
            
            if (typeof wp !== 'undefined' && wp.media) {
                var fileFrame = wp.media({
                    title: starfishData.strings.selectFile || '选择文件',
                    button: {
                        text: starfishData.strings.useThisFile || '使用此文件'
                    },
                    multiple: false
                });
                
                fileFrame.on('select', function() {
                    var attachment = fileFrame.state().get('selection').first().toJSON();
                    urlInput.value = attachment.url;
                    
                    // 如果删除按钮不存在，创建它
                    var removeBtn = wrapper.querySelector('.starfish-remove-button');
                    if (!removeBtn) {
                        removeBtn = document.createElement('button');
                        removeBtn.type = 'button';
                        removeBtn.className = 'button starfish-remove-button';
                        removeBtn.textContent = starfishData.strings.remove || '移除';
                        
                        // 插入到文件上传按钮后面
                        button.parentNode.insertBefore(removeBtn, button.nextSibling);
                        
                        // 绑定删除事件
                        bindRemoveButton(removeBtn);
                    }
                    
                    // 触发 change 事件以更新依赖
                    var event = new Event('change', { bubbles: true });
                    urlInput.dispatchEvent(event);
                });
                
                fileFrame.open();
            }
        });
    }
    
    /**
     * 绑定画廊按钮事件
     */
    function bindGalleryButton(button) {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            var wrapper = this.closest('.starfish-gallery-wrapper');
            if (!wrapper) return;
            
            var urlsInput = wrapper.querySelector('.starfish-gallery-urls');
            var previewDiv = wrapper.querySelector('.starfish-gallery-preview');
            
            if (!urlsInput || !previewDiv) return;
            
            if (typeof wp !== 'undefined' && wp.media) {
                var galleryFrame = wp.media({
                    title: starfishData.strings.manageGallery || '管理画廊',
                    button: {
                        text: starfishData.strings.addToGallery || '添加到画廊'
                    },
                    multiple: true,
                    library: {
                        type: 'image'
                    }
                });
                
                galleryFrame.on('select', function() {
                    var attachments = galleryFrame.state().get('selection').map(function(attachment) {
                        return attachment.toJSON();
                    });
                    
                    var urls = attachments.map(function(att) {
                        return att.url;
                    });
                    
                    urlsInput.value = urls.join(',');
                    
                    // 更新预览
                    updateGalleryPreview(previewDiv, urls);
                    
                    // 如果删除按钮不存在，创建它
                    var removeBtn = wrapper.querySelector('.starfish-remove-button');
                    if (!removeBtn) {
                        removeBtn = document.createElement('button');
                        removeBtn.type = 'button';
                        removeBtn.className = 'button starfish-remove-button';
                        removeBtn.textContent = starfishData.strings.remove || '移除';
                        
                        // 插入到画廊按钮后面
                        button.parentNode.insertBefore(removeBtn, button.nextSibling);
                        
                        // 绑定删除事件
                        bindRemoveButton(removeBtn);
                    }
                    
                    // 触发 change 事件
                    var event = new Event('change', { bubbles: true });
                    urlsInput.dispatchEvent(event);
                });
                
                galleryFrame.open();
            }
        });
    }
    
    /**
     * 绑定移除按钮事件
     */
    function bindRemoveButton(button) {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            var wrapper = this.closest('.starfish-upload-wrapper, .starfish-image-wrapper, .starfish-gallery-wrapper');
            if (!wrapper) return;
            
            var urlInput = wrapper.querySelector('input[type="text"], input[type="hidden"]');
            var previewDiv = wrapper.querySelector('.starfish-image-preview, .starfish-gallery-preview');
            
            if (urlInput) {
                urlInput.value = '';
            }
            
            if (previewDiv) {
                previewDiv.innerHTML = '';
            }
            
            // 移除移除按钮本身
            this.remove();
            
            // 触发 change 事件
            if (urlInput) {
                var event = new Event('change', { bubbles: true });
                urlInput.dispatchEvent(event);
            }
        });
    }

    /**
     * 初始化颜色选择器
     */
    function initColorPickers() {
        if (typeof jQuery !== 'undefined' && jQuery.wp && jQuery.wp.wpColorPicker) {
            // 如果 jQuery 和 wpColorPicker 可用，使用它
            jQuery('.starfish-color-picker').wpColorPicker();
        } else {
            // 否则使用原生实现
            var colorPickers = document.querySelectorAll('.starfish-color-picker');
            colorPickers.forEach(function(picker) {
                // 这里可以集成其他原生颜色选择器库
                // 目前保持为普通文本输入框
            });
        }
    }
    
    /**
     * 初始化上传字段
     */
    function initUploadFields() {
        var uploadButtons = document.querySelectorAll('.starfish-upload-button');
        
        uploadButtons.forEach(function(button) {
            bindFileUploadButton(button);
        });
    }
    
    /**
     * 初始化图片字段
     */
    function initImageFields() {
        var imageButtons = document.querySelectorAll('.starfish-image-button');
        
        imageButtons.forEach(function(button) {
            bindImageUploadButton(button);
        });
    }
    
    /**
     * 初始化画廊字段
     */
    function initGalleryFields() {
        var galleryButtons = document.querySelectorAll('.starfish-gallery-button');
        
        galleryButtons.forEach(function(button) {
            bindGalleryButton(button);
        });
    }
    
    /**
     * 更新画廊预览
     */
    function updateGalleryPreview(previewDiv, urls) {
        previewDiv.innerHTML = '';
        
        urls.forEach(function(url) {
            if (url) {
                var itemDiv = document.createElement('div');
                itemDiv.className = 'starfish-gallery-item';
                
                var img = document.createElement('img');
                img.src = url;
                img.style.maxWidth = '100px';
                img.style.height = 'auto';
                
                itemDiv.appendChild(img);
                previewDiv.appendChild(itemDiv);
            }
        });
    }
    
    /**
     * 初始化移除按钮
     */
    function initRemoveButtons() {
        var removeButtons = document.querySelectorAll('.starfish-remove-button');
        
        removeButtons.forEach(function(button) {
            bindRemoveButton(button);
        });
    }
    
    /**
     * 初始化群组字段
     */
    function initGroupFields() {
        var addButtons = document.querySelectorAll('.starfish-group-add');
        
        addButtons.forEach(function(button) {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                
                var fieldId = this.getAttribute('data-field-id');
                var wrapper = this.closest('.starfish-group-wrapper');
                var itemsContainer = wrapper.querySelector('.starfish-group-items');
                var template = wrapper.querySelector('.starfish-group-template[data-field-id="' + fieldId + '"]');
                
                if (!template) return;
                
                // 获取模板内容
                var templateContent = template.textContent || template.innerHTML;
                
                // 计算新索引
                var existingItems = itemsContainer.querySelectorAll('.starfish-group-item');
                var newIndex = existingItems.length;
                
                // 替换占位符 - 同时替换 __INDEX__ 和 _INDEX_
                var newItemHtml = templateContent
                    .replace(/__INDEX__/g, newIndex)
                    .replace(/_INDEX_/g, '_' + newIndex + '_');
                
                // 创建新元素
                var tempDiv = document.createElement('div');
                tempDiv.innerHTML = newItemHtml.trim();
                var newItem = tempDiv.firstChild;
                
                // 添加到容器
                itemsContainer.appendChild(newItem);
                
                // 更新组标题
                updateGroupTitles(wrapper);
                
                // 绑定删除按钮
                bindGroupRemoveButton(newItem);
                
                // 初始化新项中的特殊字段（上传、图片、画廊等）
                initializeSpecialFieldsInItem(newItem, fieldId, newIndex);
            });
        });
        
        // 绑定现有的删除按钮
        var removeButtons = document.querySelectorAll('.starfish-group-remove');
        removeButtons.forEach(function(button) {
            bindGroupRemoveButton(button.closest('.starfish-group-item'));
        });
    }
    
    /**
     * 统一绑定字段事件
     */
    function bindFieldEvents(container) {
        // 绑定图片上传按钮
        container.querySelectorAll('.starfish-image-button').forEach(function(button) {
            bindImageUploadButton(button);
        });
        
        // 绑定文件上传按钮
        container.querySelectorAll('.starfish-upload-button').forEach(function(button) {
            bindFileUploadButton(button);
        });
        
        // 绑定画廊按钮
        container.querySelectorAll('.starfish-gallery-button').forEach(function(button) {
            bindGalleryButton(button);
        });
        
        // 绑定移除按钮
        container.querySelectorAll('.starfish-remove-button').forEach(function(button) {
            bindRemoveButton(button);
        });
    }

    /**
     * 初始化群组项中的特殊字段（上传、图片、画廊等）
     */
    function initializeSpecialFieldsInItem(item, fieldId, index) {
        console.log('Initializing special fields in item, index:', index);
        
        // 重新绑定删除按钮事件
        var removeBtn = item.querySelector('.starfish-group-remove');
        if (removeBtn) {
            bindGroupRemoveButton(item);
        }
        
        // 统一绑定其他特殊字段事件
        bindFieldEvents(item);
        
        // 调试：检查是否找到了按钮
        var imageButtons = item.querySelectorAll('.starfish-image-button');
        console.log('Found image buttons:', imageButtons.length);
        
        var uploadButtons = item.querySelectorAll('.starfish-upload-button');
        console.log('Found upload buttons:', uploadButtons.length);
        
        var galleryButtons = item.querySelectorAll('.starfish-gallery-button');
        console.log('Found gallery buttons:', galleryButtons.length);
    }
    
    /**
     * 绑定群组删除按钮
     */
    function bindGroupRemoveButton(item) {
        if (!item) return;
        
        var removeBtn = item.querySelector('.starfish-group-remove');
        if (!removeBtn) return;
        
        // 移除旧的事件监听器
        var newBtn = removeBtn.cloneNode(true);
        removeBtn.parentNode.replaceChild(newBtn, removeBtn);
        
        newBtn.addEventListener('click', function(e) {
            e.preventDefault();
            
            if (confirm(starfishData.strings.confirmDelete || '确定要删除吗？')) {
                var wrapper = item.closest('.starfish-group-wrapper');
                item.remove();
                
                // 更新剩余项的索引和标题
                updateGroupIndicesAndTitles(wrapper);
            }
        });
    }
    
    /**
     * 更新群组标题
     */
    function updateGroupTitles(wrapper) {
        var items = wrapper.querySelectorAll('.starfish-group-item');
        items.forEach(function(item, index) {
            var titleSpan = item.querySelector('.starfish-group-title');
            if (titleSpan) {
                titleSpan.textContent = '项目 #' + (index + 1);
            }
        });
    }
    
    /**
     * 更新群组索引和标题
     */
    function updateGroupIndicesAndTitles(wrapper) {
        var items = wrapper.querySelectorAll('.starfish-group-item');
        items.forEach(function(item, index) {
            // 更新 data-index
            item.setAttribute('data-index', index);
            
            // 更新标题
            var titleSpan = item.querySelector('.starfish-group-title');
            if (titleSpan) {
                titleSpan.textContent = '项目 #' + (index + 1);
            }
            
            // 更新所有输入字段的 name 属性中的索引
            var inputs = item.querySelectorAll('input, select, textarea');
            inputs.forEach(function(input) {
                var name = input.getAttribute('name');
                if (name) {
                    // 替换 name 中的索引部分（假设格式为 xxx[old_index][yyy]）
                    var newName = name.replace(/\[\d+\]/, '[' + index + ']');
                    input.setAttribute('name', newName);
                }
            });
        });
    }
    
    // 排序器拖拽全局变量
    var draggedItem = null;
    var dragSourceList = null;
    
    /**
     * 初始化排序器字段（双列表拖拽）
     */
    function initSorterFields() {
        var sorterWrappers = document.querySelectorAll('.starfish-sorter-dual-wrapper');
        
        console.log('Initializing sorter fields, found:', sorterWrappers.length, 'wrappers');
        
        sorterWrappers.forEach(function(wrapper) {
            var enabledList = wrapper.querySelector('[id$="_enabled"]');
            var disabledList = wrapper.querySelector('[id$="_disabled"]');
            var outputInput = wrapper.querySelector('.starfish-sorter-output');
            
            console.log('Enabled list:', enabledList ? 'found' : 'not found');
            console.log('Disabled list:', disabledList ? 'found' : 'not found');
            console.log('Output input initial value:', outputInput ? outputInput.value : 'not found');
            
            if (enabledList) {
                makeDualSortable(enabledList, wrapper);
            }
            
            if (disabledList) {
                makeDualSortable(disabledList, wrapper);
            }
        });
    }
    
    /**
     * 使双列表可排序
     */
    function makeDualSortable(list, wrapper) {
        // 为列表本身添加 drop 事件，支持拖到空列表
        list.addEventListener('dragover', function(e) {
            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';
        });
        
        list.addEventListener('drop', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            if (draggedItem && draggedItem.parentNode !== this) {
                // 从另一个列表拖过来
                this.appendChild(draggedItem);
                draggedItem.classList.remove('dragging');
                
                // 清除所有 drag-over 样式
                clearDragOverStyles(wrapper);
                
                // 更新隐藏字段的值
                updateDualSorterOutput(wrapper);
            }
        });
        
        // 初始化列表项
        initializeListItems(list, wrapper);
    }
    
    /**
     * 初始化列表项的拖拽事件
     */
    function initializeListItems(list, wrapper) {
        var items = list.querySelectorAll('.starfish-sorter-item');
        
        items.forEach(function(item) {
            // 拖拽开始
            item.addEventListener('dragstart', function(e) {
                draggedItem = this;
                dragSourceList = this.parentNode;
                
                setTimeout(function() {
                    item.classList.add('dragging');
                }, 0);
                
                e.dataTransfer.effectAllowed = 'move';
                e.dataTransfer.setData('text/html', this.innerHTML);
            });
            
            // 拖拽结束
            item.addEventListener('dragend', function(e) {
                item.classList.remove('dragging');
                
                // 清除所有 drag-over 样式
                clearDragOverStyles(wrapper);
                
                draggedItem = null;
                dragSourceList = null;
                
                // 更新隐藏字段的值
                updateDualSorterOutput(wrapper);
            });
            
            // 拖拽经过
            item.addEventListener('dragover', function(e) {
                e.preventDefault();
                e.dataTransfer.dropEffect = 'move';
            });
            
            // 拖拽进入
            item.addEventListener('dragenter', function(e) {
                e.preventDefault();
                if (this !== draggedItem && this.parentNode === draggedItem.parentNode) {
                    this.classList.add('drag-over');
                }
            });
            
            // 拖拽离开
            item.addEventListener('dragleave', function(e) {
                this.classList.remove('drag-over');
            });
            
            // 放置
            item.addEventListener('drop', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                this.classList.remove('drag-over');
                
                if (this !== draggedItem) {
                    var allItems = Array.from(this.parentNode.querySelectorAll('.starfish-sorter-item'));
                    var draggedIndex = allItems.indexOf(draggedItem);
                    var droppedIndex = allItems.indexOf(this);
                    
                    if (draggedIndex < droppedIndex) {
                        this.parentNode.insertBefore(draggedItem, this.nextSibling);
                    } else {
                        this.parentNode.insertBefore(draggedItem, this);
                    }
                }
            });
        });
    }
    
    /**
     * 清除所有 drag-over 样式
     */
    function clearDragOverStyles(wrapper) {
        var allItems = wrapper.querySelectorAll('.starfish-sorter-item');
        allItems.forEach(function(i) {
            i.classList.remove('drag-over');
        });
    }
    
    /**
     * 更新双列表排序器输出
     */
    function updateDualSorterOutput(wrapper) {
        var outputInput = wrapper.querySelector('.starfish-sorter-output');
        var enabledList = wrapper.querySelector('[id$="_enabled"]');
        
        if (!outputInput || !enabledList) return;
        
        var items = enabledList.querySelectorAll('.starfish-sorter-item');
        var values = [];
        
        items.forEach(function(item) {
            var value = item.getAttribute('data-value');
            if (value) {
                values.push(value);
            }
        });
        
        // 使用 JSON 格式存储
        outputInput.value = JSON.stringify(values);
    }
    
    /**
     * 初始化滑块
     */
    function initSliders() {
        var sliders = document.querySelectorAll('.starfish-slider');
        
        sliders.forEach(function(slider) {
            var valueDisplay = slider.parentElement.querySelector('.starfish-slider-value');
            
            if (valueDisplay) {
                // 更新初始值
                var unit = valueDisplay.textContent.replace(/[0-9]/g, '');
                valueDisplay.textContent = slider.value + unit;
                
                // 监听变化
                slider.addEventListener('input', function() {
                    valueDisplay.textContent = this.value + unit;
                });
                
                slider.addEventListener('change', function() {
                    valueDisplay.textContent = this.value + unit;
                });
            }
        });
    }
    
    /**
     * 初始化备份字段
     */
    function initBackupFields() {
        var exportButton = document.getElementById('starfish-export-btn');
        var importButton = document.getElementById('starfish-import-btn');
        
        if (exportButton) {
            exportButton.addEventListener('click', function(e) {
                e.preventDefault();
                
                var backupData = document.getElementById('starfish-backup-data');
                if (!backupData) return;
                
                var data = backupData.value;
                
                // 创建 Blob 对象
                var blob = new Blob([data], { type: 'application/json' });
                
                // 创建下载链接
                var url = URL.createObjectURL(blob);
                var a = document.createElement('a');
                a.href = url;
                
                // 生成文件名（包含时间戳）
                var timestamp = new Date().toISOString().replace(/[:.]/g, '-').slice(0, -5);
                a.download = 'starfish-backup-' + timestamp + '.json';
                
                // 触发下载
                document.body.appendChild(a);
                a.click();
                
                // 清理
                setTimeout(function() {
                    document.body.removeChild(a);
                    URL.revokeObjectURL(url);
                }, 100);
            });
        }
        
        if (importButton) {
            importButton.addEventListener('click', function(e) {
                e.preventDefault();
                
                var fileInput = document.getElementById('starfish-import-file');
                if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
                    alert(starfishData.strings.selectFileFirst || '请先选择一个JSON文件');
                    return;
                }
                
                if (confirm(starfishData.strings.confirmImport || '确定要导入设置吗？这将覆盖当前的配置。')) {
                    // 创建 FormData 对象
                    var formData = new FormData();
                    formData.append('starfish_import_file', fileInput.files[0]);
                    formData.append('action', 'starfish_import_settings');
                    formData.append('nonce', starfishData.nonce);
                    
                    // 显示加载状态
                    var originalText = this.innerHTML;
                    this.innerHTML = '<span class="dashicons dashicons-update"></span> 导入中...';
                    this.disabled = true;
                    
                    // 发送 AJAX 请求
                    fetch(starfishData.ajaxUrl, {
                        method: 'POST',
                        body: formData
                    })
                    .then(function(response) {
                        return response.json();
                    })
                    .then(function(data) {
                        if (data.success) {
                            alert(data.message || '导入成功！');
                            // 清空文件选择
                            fileInput.value = '';
                            // 刷新页面以显示新数据
                            window.location.reload();
                        } else {
                            alert(data.message || '导入失败，请重试。');
                        }
                    })
                    .catch(function(error) {
                        console.error('Import error:', error);
                        alert('导入过程中发生错误，请重试。');
                    })
                    .finally(function() {
                        // 恢复按钮状态
                        importButton.innerHTML = originalText;
                        importButton.disabled = false;
                    });
                }
            });
        }
        
        // 重置设置按钮
        var resetButton = document.getElementById('starfish-reset-btn');
        if (resetButton) {
            resetButton.addEventListener('click', function(e) {
                e.preventDefault();
                
                if (confirm(starfishData.strings.confirmReset || '确定要重置为默认设置吗？这将清除所有自定义配置！')) {
                    // 创建 FormData 对象
                    var formData = new FormData();
                    formData.append('action', 'starfish_reset_settings');
                    formData.append('nonce', starfishData.nonce);
                    
                    // 显示加载状态
                    var originalText = this.innerHTML;
                    this.innerHTML = '<span class="dashicons dashicons-update"></span> 重置中...';
                    this.disabled = true;
                    
                    // 发送 AJAX 请求
                    fetch(starfishData.ajaxUrl, {
                        method: 'POST',
                        body: formData
                    })
                    .then(function(response) {
                        return response.json();
                    })
                    .then(function(data) {
                        if (data.success) {
                            alert(data.message || '重置成功！');
                            // 刷新页面以显示默认数据
                            window.location.reload();
                        } else {
                            alert(data.message || '重置失败，请重试。');
                        }
                    })
                    .catch(function(error) {
                        console.error('Reset error:', error);
                        alert('重置过程中发生错误，请重试。');
                    })
                    .finally(function() {
                        // 恢复按钮状态
                        resetButton.innerHTML = originalText;
                        resetButton.disabled = false;
                    });
                }
            });
        }
    }
    
})();

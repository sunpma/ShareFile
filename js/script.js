// js/script.js

document.addEventListener('DOMContentLoaded', function() {
    // --- Sorting Functionality (Retained) ---
    const sortableHeaders = document.querySelectorAll('.file-list th.sortable');

    sortableHeaders.forEach(header => {
        header.addEventListener('click', function() {
            const sortBy = this.dataset.sort;
            let currentOrder = this.dataset.order || 'asc';

            const newOrder = (currentOrder === 'asc') ? 'desc' : 'asc';
            this.dataset.order = newOrder;

            const url = new URL(window.location.href);
            url.searchParams.set('sort', sortBy);
            url.searchParams.set('order', newOrder);
            window.location.href = url.toString();
        });

        const urlParams = new URLSearchParams(window.location.search);
        const currentSort = urlParams.get('sort');
        const currentOrder = urlParams.get('order');

        if (header.dataset.sort === currentSort) {
            header.classList.add(currentOrder);
            header.querySelector('.sort-icon .fa-sort').style.display = 'none';
            if (currentOrder === 'asc') {
                header.querySelector('.sort-icon .fa-sort-up').style.display = 'inline-block';
            } else {
                header.querySelector('.sort-icon .fa-sort-down').style.display = 'inline-block';
            }
        }
    });

    // --- Modal Functionality ---

    // Get modals
    const linkModal = document.getElementById('linkModal');
    const imageModal = document.getElementById('imageModal');

    // Get close buttons
    const closeButtons = document.querySelectorAll('.close-button');
    closeButtons.forEach(button => {
        button.addEventListener('click', function() {
            const modal = this.closest('.modal');
            if (modal) {
                modal.classList.add('closing'); // Add closing class for animation
                modal.addEventListener('animationend', function() {
                    modal.style.display = 'none';
                    modal.classList.remove('closing');
                }, { once: true }); // Ensure event listener runs only once
            }
        });
    });

    // Close modal when clicking outside of modal content
    window.addEventListener('click', function(event) {
        if (event.target === linkModal) {
            linkModal.classList.add('closing');
            linkModal.addEventListener('animationend', function() {
                linkModal.style.display = 'none';
                linkModal.classList.remove('closing');
            }, { once: true });
        }
        if (event.target === imageModal) {
            imageModal.classList.add('closing');
            imageModal.addEventListener('animationend', function() {
                imageModal.style.display = 'none';
                imageModal.classList.remove('closing');
            }, { once: true });
        }
    });

    // --- File Link Modal ---
    const showLinkCardButtons = document.querySelectorAll('.show-link-card');
    const fileDirectLinkInput = document.getElementById('fileDirectLink');
    // const fileDirectLinkDownloadButton = document.getElementById('fileDirectLinkDownload'); // 移除此行

    showLinkCardButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault(); // Prevent default download behavior
            const link = this.dataset.link;
            fileDirectLinkInput.value = link;
            // fileDirectLinkDownloadButton.href = link; // 移除此行
            linkModal.style.display = 'flex'; // Show modal
        });
    });

    // --- Image Modal ---
    const imageItems = document.querySelectorAll('.image-item');
    const modalImage = document.getElementById('modalImage');
    const imageUrlInput = document.getElementById('imageUrl');
    const imageHtmlInput = document.getElementById('imageHtml');
    const imageBbcodeInput = document.getElementById('imageBbcode');
    const imageMarkdownInput = document.getElementById('imageMarkdown');

    imageItems.forEach(item => {
        item.addEventListener('click', function() {
            const fullUrl = this.dataset.fullUrl;
            const imageName = this.dataset.imageName;

            modalImage.src = fullUrl;
            modalImage.alt = imageName;

            // Set all link inputs
            imageUrlInput.value = fullUrl;
            imageHtmlInput.value = `<img src="${fullUrl}" alt="${imageName}">`;
            imageBbcodeInput.value = `[img]${fullUrl}[/img]`;
            imageMarkdownInput.value = `![${imageName}](${fullUrl})`;

            imageModal.style.display = 'flex'; // Show image modal
        });
    });

    // --- Copy Button Functionality ---
    const copyButtons = document.querySelectorAll('.copy-button');
    copyButtons.forEach(button => {
        button.addEventListener('click', function() {
            const targetId = this.dataset.target;
            const targetInput = document.getElementById(targetId);
            const originalButtonHTML = this.innerHTML; // 保存按钮原始的HTML内容 (例如 <i class="fas fa-copy"></i> 复制)

            if (targetInput) {
                targetInput.select();
                targetInput.setSelectionRange(0, 99999); // 兼容移动设备

                try {
                    document.execCommand('copy'); // 执行复制操作

                    // 复制成功：修改按钮文本和图标，并暂时禁用按钮
                    this.innerHTML = '<i class="fas fa-check"></i> 已复制!';
                    this.disabled = true;

                    // 2秒后恢复按钮的原始状态
                    setTimeout(() => {
                        this.innerHTML = originalButtonHTML;
                        this.disabled = false;
                    }, 2000);

                } catch (err) {
                    console.error('复制失败:', err);

                    // 复制失败：修改按钮文本和图标，并暂时禁用按钮
                    this.innerHTML = '<i class="fas fa-times"></i> 复制失败';
                    this.disabled = true;

                    // 2.5秒后恢复按钮的原始状态
                    setTimeout(() => {
                        this.innerHTML = originalButtonHTML;
                        this.disabled = false;
                    }, 2500);
                }
            }
        });
    });

});
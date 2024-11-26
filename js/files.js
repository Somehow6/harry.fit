// 检查登录状态
let currentUser = null;

document.addEventListener('DOMContentLoaded', async () => {
    // 先检查登录状态
    const isLoggedIn = await checkLoginStatus();
    
    if (isLoggedIn) {
        // 加载文件列表
        loadFiles();
    }
});

async function checkLoginStatus() {
    try {
        const response = await fetch('/api/check_login.php');
        const data = await response.json();
        console.log('Login status check response:', data); // 调试输出
        
        if (data.success && data.user) {
            currentUser = data.user;
            return true;
        } else {
            window.location.href = 'index.html';
            return false;
        }
    } catch (error) {
        console.error('Error checking login status:', error);
        window.location.href = 'index.html';
        return false;
    }
}


// 加载文件列表
async function loadFiles() {
    try {
        const response = await fetch('/api/get_files.php');
        const data = await response.json();
        if (data.success) {
            displayFiles(data.files);
        } else {
            alert('加载文件列表失败：' + data.error);
        }
    } catch (error) {
        console.error('Error loading files:', error);
        alert('加载文件列表失败');
    }
}

// 显示文件列表
function displayFiles(files) {
    const container = document.getElementById('files-container');
    container.innerHTML = '';

    files.forEach(file => {
        const fileItem = document.createElement('div');
        fileItem.className = 'file-item';
        fileItem.innerHTML = `
            <div class="file-name">${file.name}</div>
            <div class="file-actions">
                <button onclick="downloadFile(${file.id})" class="pink-button">下载</button>
                <button onclick="deleteFile(${file.id})" class="delete-btn">删除</button>
            </div>
        `;
        container.appendChild(fileItem);
    });
}

// 文件上传处理
document.getElementById('upload-form').addEventListener('submit', async (e) => {
    e.preventDefault();

    const formData = new FormData();
    const fileInput = document.getElementById('file-input');
    formData.append('file', fileInput.files[0]);

    try {
        const response = await fetch('/api/upload_file.php', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();
        
        if (data.success) {
            alert('文件上传成功');
            loadFiles(); // 重新加载文件列表
        } else {
            alert('文件上传失败：' + data.error);
        }
    } catch (error) {
        console.error('Error uploading file:', error);
        alert('文件上传失败');
    }
});

// 删除文件
async function deleteFile(fileId) {
    if (!confirm('确定要删除这个文件吗？')) {
        return;
    }

    try {
        const response = await fetch('/api/delete_file.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ file_id: fileId })
        });
        const data = await response.json();
        
        if (data.success) {
            alert('文件删除成功');
            loadFiles(); // 重新加载文件列表
        } else {
            alert('文件删除失败：' + data.error);
        }
    } catch (error) {
        console.error('Error deleting file:', error);
        alert('文件删除失败');
    }
}

// 下载文件
function downloadFile(fileId) {
    window.location.href = `/api/download_file.php?file_id=${fileId}`;
}
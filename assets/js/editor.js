
// Initialize variables to store editor instances and state
let editor;
let selectedTemplate = null;
let saveToast;
let lastSaveTime = null;

document.addEventListener('DOMContentLoaded', function() {
    saveToast = new bootstrap.Toast(document.getElementById('saveToast'));
    
    // Configure Monaco Editor
    require.config({ paths: { 'vs': 'https://cdnjs.cloudflare.com/ajax/libs/monaco-editor/0.36.1/min/vs' }});
    require(['vs/editor/editor.main'], function() {
        // Create editor instance
        editor = monaco.editor.create(document.getElementById('editor-area'), {
            value: fileContent,
            language: 'dockerfile',
            theme: 'vs-dark',
            automaticLayout: true,
            fontFamily: 'JetBrains Mono, Fira Code, Menlo, Monaco, Consolas, monospace',
            fontSize: 14,
            minimap: { enabled: true },
            scrollBeyondLastLine: false,
            wordWrap: 'on',
            renderLineHighlight: 'all',
            renderWhitespace: 'selection',
            bracketPairColorization: { enabled: true },
            autoIndent: 'full',
            formatOnPaste: true,
            formatOnType: true,
            lineNumbers: 'on',
            lineDecorationsWidth: 10,
            suggestSelection: 'first',
            tabSize: 2,
            scrollbar: {
                vertical: 'auto',
                horizontal: 'auto',
                useShadows: true,
                verticalHasArrows: false,
                horizontalHasArrows: false,
                verticalScrollbarSize: 12,
                horizontalScrollbarSize: 12
            }
        });
        
        // Update cursor position in the status bar
        editor.onDidChangeCursorPosition(function(e) {
            document.getElementById('editor-position').textContent = 
                `Line: ${e.position.lineNumber}, Column: ${e.position.column}`;
        });
        
        // Add keyboard shortcut for save (Ctrl+S)
        editor.addCommand(monaco.KeyMod.CtrlCmd | monaco.KeyCode.KeyS, saveDockerfile);
        
        // Appearance controls
        document.getElementById('editor-theme').addEventListener('change', function(e) {
            monaco.editor.setTheme(e.target.value);
        });
        
        document.getElementById('editor-font-size').addEventListener('change', function(e) {
            editor.updateOptions({ fontSize: parseInt(e.target.value) });
        });
        
        document.getElementById('word-wrap').addEventListener('change', function(e) {
            editor.updateOptions({ wordWrap: e.target.checked ? 'on' : 'off' });
        });
        
        // Auto-save timer (every 30 seconds)
        setInterval(function() {
            if (editor && editor.getValue() !== fileContent) {
                saveDockerfile(true);
            }
        }, 30000);
    });
    
    // Template and instruction handling
    document.querySelectorAll('.template-item').forEach(item => {
        item.addEventListener('click', function() {
            insertDockerInstruction(this.getAttribute('data-template'));
        });
    });
    
    document.querySelectorAll('.template-card').forEach(card => {
        card.addEventListener('click', function() {
            selectedTemplate = this.getAttribute('data-template');
            
            // Show confirmation modal
            const templateModal = new bootstrap.Modal(document.getElementById('templateModal'));
            templateModal.show();
        });
    });
    
    document.getElementById('confirmTemplate').addEventListener('click', function() {
        applyDockerTemplate(selectedTemplate);
        bootstrap.Modal.getInstance(document.getElementById('templateModal')).hide();
    });
    
    // Save button
    document.getElementById('saveButton').addEventListener('click', saveDockerfile);
});

function saveDockerfile(silent = false) {
    if (!editor) return;
    
    const content = editor.getValue();
    
    fetch('/dockerfile/save/' + dockerfileName + '.dockerfile', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            fileContent: content,
        }),
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Save failed: ' + response.statusText);
        }
        return response.json();
    })
    .then(data => {
        if (data.status === 'ok') {
            if (!silent) {
                saveToast.show();
            }
            
            // Update last save time
            lastSaveTime = new Date();
            document.getElementById('save-status').textContent = 
                `Last saved: ${lastSaveTime.toLocaleTimeString()}`;
        } else {
            throw new Error('Save failed');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error saving the file: ' + error.message);
    });
}

function insertDockerInstruction(instruction) {
    if (!editor) return;
    
    const templates = {
        'FROM': 'FROM base_image:tag\n',
        'WORKDIR': 'WORKDIR /app\n',
        'COPY': 'COPY source_path dest_path\n',
        'RUN': 'RUN command\n',
        'ENV': 'ENV key=value\n',
        'EXPOSE': 'EXPOSE port\n',
        'CMD': 'CMD ["executable", "param1", "param2"]\n'
    };
    
    const snippet = templates[instruction] || '';
    
    if (snippet) {
        // Get cursor position
        const selection = editor.getSelection();
        const position = selection 
            ? { lineNumber: selection.positionLineNumber, column: selection.positionColumn } 
            : editor.getPosition();
        
        // Insert the snippet
        editor.executeEdits('', [{
            range: {
                startLineNumber: position.lineNumber,
                startColumn: position.column,
                endLineNumber: position.lineNumber,
                endColumn: position.column
            },
            text: snippet
        }]);
        
        // Focus the editor after insertion
        editor.focus();
    }
}

function applyDockerTemplate(template) {
    if (!editor) return;
    
    const templates = {
        'nginx': `FROM nginx:alpine

# Copy custom nginx config
COPY nginx.conf /etc/nginx/conf.d/default.conf

# Copy static website content
COPY ./html /usr/share/nginx/html

# Expose port 80
EXPOSE 80

# Start Nginx
CMD ["nginx", "-g", "daemon off;"]`,
        'node': `FROM node:16-alpine

# Create app directory
WORKDIR /usr/src/app

# Copy package files
COPY package*.json ./

# Install dependencies
RUN npm install

# Copy app source
COPY . .

# Expose the port
EXPOSE 3000

# Start the app
CMD ["node", "index.js"]`,
        'php': `FROM php:8.1-apache

# Install dependencies
RUN apt-get update && apt-get install -y \\
libpng-dev \\
libjpeg-dev \\
libfreetype6-dev \\
zip \\
unzip \\
&& docker-php-ext-configure gd --with-freetype --with-jpeg \\
&& docker-php-ext-install gd pdo pdo_mysql

# Enable apache rewrite module
RUN a2enmod rewrite

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . .

# Set permissions
RUN chown -R www-data:www-data /var/www/html

# Expose port 80
EXPOSE 80

# Start Apache
CMD ["apache2-foreground"]`,
        'python': `FROM python:3.10-slim

# Set working directory
WORKDIR /app

# Copy requirements file
COPY requirements.txt .

# Install dependencies
RUN pip install --no-cache-dir -r requirements.txt

# Copy application code
COPY . .

# Expose port if needed
# EXPOSE 8000

# Set environment variables
ENV PYTHONUNBUFFERED=1

# Run the application
CMD ["python", "app.py"]`
    };
    
    const templateContent = templates[template] || '';
    
    if (templateContent) {
        // Set the editor content
        editor.setValue(templateContent);
        
        // Focus the editor after setting content
        editor.focus();
    }
}
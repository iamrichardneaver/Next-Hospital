// CKEditor Configuration for Medical Consultation Forms
document.addEventListener('DOMContentLoaded', function() {
    // CKEditor configuration for medical forms
    const editorConfig = {
        toolbar: {
            items: [
                'heading', '|',
                'bold', 'italic', 'underline', 'strikethrough', '|',
                'bulletedList', 'numberedList', '|',
                'outdent', 'indent', '|',
                'blockQuote', 'insertTable', '|',
                'undo', 'redo'
            ]
        },
        language: 'en',
        table: {
            contentToolbar: [
                'tableColumn',
                'tableRow',
                'mergeTableCells'
            ]
        },
        heading: {
            options: [
                { model: 'paragraph', title: 'Paragraph', class: 'ck-heading_paragraph' },
                { model: 'heading1', view: 'h1', title: 'Heading 1', class: 'ck-heading_heading1' },
                { model: 'heading2', view: 'h2', title: 'Heading 2', class: 'ck-heading_heading2' },
                { model: 'heading3', view: 'h3', title: 'Heading 3', class: 'ck-heading_heading3' }
            ]
        }
    };

    // Initialize CKEditor for consultation form fields
    const editorFields = [
        'chief_complaint',
        'history_of_present_illness',
        'on_direct_questioning',
        'past_medical_history_others',
        'drug_history',
        'allergy_history',
        'family_history',
        'social_history',
        'general_examination',
        'cardiovascular_examination',
        'respiratory_examination',
        'abdominal_examination',
        'neurological_examination',
        'doctors_impression',
        'medications_prescribed',
        'investigations_ordered',
        'follow_up_instructions',
        // Radiology fields
        'clinical_history',
        'clinical_question',
        'indication',
        'technician_notes',
        'rejection_reason',
        'study_description',
        'study_notes',
        'technique_notes',
        'findings',
        'impression',
        'recommendations',
        'amendment_reason'
    ];

    // Initialize CKEditor for each field
    editorFields.forEach(fieldId => {
        const element = document.getElementById(fieldId);
        if (element) {
            ClassicEditor
                .create(element, editorConfig)
                .then(editor => {
                    console.log(`CKEditor initialized for ${fieldId}`);
                    
                    // Add a custom CSS class for styling
                    editor.ui.view.editable.element.classList.add('ck-editor__editable--medical');
                    
                    // Update the form field when editor content changes
                    editor.model.document.on('change:data', () => {
                        const data = editor.getData();
                        element.value = data;
                    });
                })
                .catch(error => {
                    console.error(`Error initializing CKEditor for ${fieldId}:`, error);
                });
        }
    });
});

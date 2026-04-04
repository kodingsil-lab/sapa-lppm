document.addEventListener('DOMContentLoaded', function () {
    function initCustomPagination(table) {
        if (!table || table.dataset.paginationReady === '1') {
            return;
        }

        const tbody = table.querySelector('tbody');
        const perPage = parseInt(table.dataset.customPagination || '10', 10);
        if (!tbody || !Number.isFinite(perPage) || perPage < 1) {
            return;
        }

        const rows = Array.from(tbody.querySelectorAll('tr')).filter(function (row) {
            return row.querySelectorAll('td').length > 0;
        });

        if (rows.length === 0) {
            return;
        }

        if (rows.length === 1 && rows[0].querySelector('td[colspan]')) {
            return;
        }

        const totalPages = Math.ceil(rows.length / perPage);
        if (totalPages <= 1) {
            return;
        }

        if (!table.id) {
            table.id = 'customTable' + Math.random().toString(36).slice(2, 10);
        }

        const wrapper = table.closest('.table-responsive') || table.parentElement;
        if (!wrapper) {
            return;
        }

        const footer = document.createElement('div');
        footer.className = 'table-pagination-footer';
        footer.setAttribute('data-pagination-for', table.id);

        const info = document.createElement('div');
        info.className = 'table-pagination-info';

        const nav = document.createElement('div');
        nav.className = 'table-pagination-nav';

        footer.appendChild(info);
        footer.appendChild(nav);
        wrapper.insertAdjacentElement('afterend', footer);

        const firstHeader = table.querySelector('thead th');
        const firstHeaderText = firstHeader ? String(firstHeader.textContent || '').trim().toLowerCase() : '';
        const shouldRenumber = /^no\.?$/.test(firstHeaderText);

        function updateRowNumbers(startIndex, visibleRows) {
            if (!shouldRenumber) {
                return;
            }

            visibleRows.forEach(function (row, index) {
                const firstCell = row.querySelector('td');
                if (firstCell) {
                    firstCell.textContent = String(startIndex + index + 1);
                }
            });
        }

        function createButton(label, page, options) {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'table-page-btn';
            button.textContent = label;

            if (options.current) {
                button.classList.add('current');
                button.setAttribute('aria-current', 'page');
            }

            if (options.disabled) {
                button.disabled = true;
                button.classList.add('disabled');
            } else {
                button.addEventListener('click', function () {
                    render(page);
                });
            }

            return button;
        }

        function render(page) {
            const currentPage = Math.min(Math.max(page, 1), totalPages);
            const start = (currentPage - 1) * perPage;
            const end = Math.min(start + perPage, rows.length);
            const visibleRows = rows.slice(start, end);

            rows.forEach(function (row, index) {
                row.style.display = index >= start && index < end ? '' : 'none';
            });

            updateRowNumbers(start, visibleRows);
            info.textContent = 'Menampilkan ' + (start + 1) + ' - ' + end + ' dari ' + rows.length + ' data';

            nav.innerHTML = '';
            nav.appendChild(createButton('Sebelumnya', currentPage - 1, { disabled: currentPage === 1, current: false }));

            let startPage = Math.max(1, currentPage - 2);
            let endPage = Math.min(totalPages, startPage + 4);
            startPage = Math.max(1, endPage - 4);

            for (let pageNumber = startPage; pageNumber <= endPage; pageNumber += 1) {
                nav.appendChild(createButton(String(pageNumber), pageNumber, {
                    disabled: false,
                    current: pageNumber === currentPage
                }));
            }

            nav.appendChild(createButton('Berikutnya', currentPage + 1, { disabled: currentPage === totalPages, current: false }));
        }

        table.dataset.paginationReady = '1';
        render(1);
    }

    function initializeCustomPaginations(scope) {
        const root = scope || document;
        const tables = root.querySelectorAll(
            'table[data-custom-pagination], .activity-table-wrap table, .status-table-wrap table, .submission-contract-table-wrap table'
        );

        tables.forEach(initCustomPagination);
    }

    window.initializeCustomPaginations = initializeCustomPaginations;
    initializeCustomPaginations(document);

    const appSidebar = document.getElementById('appSidebar');
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebarBackdrop = document.getElementById('sidebarBackdrop');
    const mobileSidebarQuery = window.matchMedia('(max-width: 991.98px)');

    function setSidebarOpen(isOpen) {
        document.body.classList.toggle('sidebar-open', isOpen);

        if (appSidebar) {
            appSidebar.classList.toggle('show', isOpen);
            appSidebar.classList.toggle('collapsed', isOpen);
            appSidebar.setAttribute('aria-hidden', isOpen ? 'false' : 'true');
        }

        if (sidebarToggle) {
            sidebarToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        }
    }

    if (appSidebar) {
        appSidebar.setAttribute('aria-hidden', mobileSidebarQuery.matches ? 'true' : 'false');
    }

    if (sidebarToggle) {
        sidebarToggle.setAttribute('aria-controls', 'appSidebar');
        sidebarToggle.setAttribute('aria-expanded', 'false');
    }

    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function () {
            setSidebarOpen(!document.body.classList.contains('sidebar-open'));
        });
    }

    if (sidebarBackdrop) {
        sidebarBackdrop.addEventListener('click', function () {
            setSidebarOpen(false);
        });
    }

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && document.body.classList.contains('sidebar-open')) {
            setSidebarOpen(false);
        }
    });

    mobileSidebarQuery.addEventListener('change', function (event) {
        if (!event.matches) {
            setSidebarOpen(false);
            if (appSidebar) {
                appSidebar.classList.remove('show', 'collapsed');
                appSidebar.setAttribute('aria-hidden', 'false');
            }
            return;
        }

        if (appSidebar) {
            appSidebar.setAttribute('aria-hidden', document.body.classList.contains('sidebar-open') ? 'false' : 'true');
        }
    });

    document.querySelectorAll('#appSidebar a').forEach(function (link) {
        link.addEventListener('click', function () {
            if (mobileSidebarQuery.matches) {
                setSidebarOpen(false);
            }
        });
    });

    const barEl = document.getElementById('lettersBarChart');
    if (barEl && window.Chart) {
        const labels = JSON.parse(barEl.dataset.labels || '[]');
        const values = JSON.parse(barEl.dataset.values || '[]');

        new Chart(barEl, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Jumlah Surat',
                    data: values,
                    backgroundColor: '#123C6B',
                    borderRadius: 8,
                    maxBarThickness: 28
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false,
                    },
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 5,
                        },
                    },
                },
            },
        });
    }

    const pieEl = document.getElementById('lettersPieChart');
    if (pieEl && window.Chart) {
        const labels = JSON.parse(pieEl.dataset.labels || '[]');
        const values = JSON.parse(pieEl.dataset.values || '[]');

        new Chart(pieEl, {
            type: 'pie',
            data: {
                labels: labels,
                datasets: [{
                    data: values,
                    backgroundColor: ['#123C6B', '#2E5F96', '#5E8FC6'],
                    borderWidth: 0,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            boxWidth: 12,
                            padding: 14,
                        },
                    },
                },
            },
        });
    }

    const researchPermitForm = document.getElementById('researchPermitForm');
    if (researchPermitForm) {
        researchPermitForm.addEventListener('submit', function (event) {
            const startDate = document.getElementById('startDateField');
            const endDate = document.getElementById('endDateField');

            if (startDate && endDate && startDate.value && endDate.value && endDate.value < startDate.value) {
                endDate.setCustomValidity('Tanggal selesai tidak boleh lebih kecil dari tanggal mulai.');
            } else if (endDate) {
                endDate.setCustomValidity('');
            }

            if (!researchPermitForm.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }

            researchPermitForm.classList.add('was-validated');
        });
    }

    const applicantSelect = document.getElementById('applicantSelect');
    if (applicantSelect) {
        applicantSelect.addEventListener('change', function () {
            const selected = applicantSelect.options[applicantSelect.selectedIndex];
            if (!selected) {
                return;
            }

            const mapping = [
                ['applicantName', 'name'],
                ['applicantNidn', 'nidn'],
                ['applicantEmail', 'email'],
                ['applicantPhone', 'phone'],
                ['applicantUnit', 'unit'],
            ];

            mapping.forEach(function (pair) {
                const field = document.getElementById(pair[0]);
                if (field) {
                    field.value = selected.dataset[pair[1]] || '';
                }
            });

            if (selected.dataset.unit) {
                syncFacultyProgramFromUnit(selected.dataset.unit);
            }
        });
    }

    const facultySelect = document.getElementById('facultySelect');
    const programSelect = document.getElementById('programSelect');
    const unitInput = document.getElementById('applicantUnit');
    const facultyNameInput = document.getElementById('facultyNameInput');
    const facultyPrograms = {
        FTP: {
            label: 'Fakultas Teknik dan Perencanaan (FTP)',
            programs: [
                'Teknik Lingkungan (TL)',
                'Teknik Informatika (TI)',
            ],
        },
        FMIPA: {
            label: 'Fakultas Matematika dan Ilmu Pengetahuan Alam (FMIPA)',
            programs: [
                'Statistika (STAT)',
                'Matematika (MAT)',
                'Fisika (FIS)',
                'Biologi (BO)',
            ],
        },
        FKIP: {
            label: 'Fakultas Keguruan dan Ilmu Pendidikan (FKIP)',
            programs: [
                'Pendidikan Luar Biasa (PLB)',
                'Pendidikan Jasmani, Kesehatan, dan Rekreasi (PJKR)',
                'Pendidikan Bahasa Inggris (PBI)',
                'Pendidikan Guru Sekolah Dasar (PGSD)',
            ],
        },
    };

    function setProgramOptions(facultyKey, selectedProgram) {
        if (!programSelect) {
            return;
        }
        programSelect.innerHTML = '<option value="">-- Pilih Program Studi --</option>';
        if (!facultyKey || !facultyPrograms[facultyKey]) {
            programSelect.disabled = true;
            return;
        }

        facultyPrograms[facultyKey].programs.forEach(function (program) {
            const opt = document.createElement('option');
            opt.value = program;
            opt.textContent = program;
            programSelect.appendChild(opt);
        });
        programSelect.disabled = false;
        if (selectedProgram) {
            programSelect.value = selectedProgram;
        }
    }

    function updateUnitFromProgram() {
        if (!unitInput || !programSelect) {
            return;
        }
        unitInput.value = programSelect.value || '';
    }

    function syncFacultyProgramFromUnit(unitValue) {
        if (!facultySelect || !programSelect) {
            return;
        }
        let matchedFaculty = '';
        let matchedProgram = '';
        Object.keys(facultyPrograms).forEach(function (facultyKey) {
            facultyPrograms[facultyKey].programs.forEach(function (program) {
                if (program === unitValue) {
                    matchedFaculty = facultyKey;
                    matchedProgram = program;
                }
            });
        });

        if (matchedFaculty) {
            facultySelect.value = matchedFaculty;
            if (facultyNameInput) {
                facultyNameInput.value = facultyPrograms[matchedFaculty].label;
            }
            setProgramOptions(matchedFaculty, matchedProgram);
            updateUnitFromProgram();
        } else {
            setProgramOptions('');
        }
    }

    function syncFacultyName() {
        if (!facultySelect || !facultyNameInput) {
            return;
        }
        const selected = facultySelect.value;
        facultyNameInput.value = selected && facultyPrograms[selected] ? facultyPrograms[selected].label : '';
    }

    if (facultySelect && programSelect && unitInput) {
        facultySelect.addEventListener('change', function () {
            setProgramOptions(facultySelect.value);
            syncFacultyName();
            updateUnitFromProgram();
        });

        programSelect.addEventListener('change', updateUnitFromProgram);

        setProgramOptions('');
        if (facultyNameInput && facultyNameInput.value) {
            const matchedKey = Object.keys(facultyPrograms).find(function (key) {
                return facultyPrograms[key].label === facultyNameInput.value;
            });
            if (matchedKey) {
                facultySelect.value = matchedKey;
                setProgramOptions(matchedKey);
            }
        } else if (unitInput.value) {
            syncFacultyProgramFromUnit(unitInput.value);
        } else {
            programSelect.disabled = true;
        }
    }

    const fundingSourceSelect = document.getElementById('fundingSourceSelect');
    const fundingOtherWrap = document.querySelector('.funding-other-wrap');
    const fundingOtherInput = document.getElementById('fundingSourceOther');
    if (fundingSourceSelect && fundingOtherWrap && fundingOtherInput) {
        const toggleFundingOther = function () {
            const isOther = fundingSourceSelect.value === 'Lainnya';
            fundingOtherWrap.classList.toggle('d-none', !isOther);
            fundingOtherInput.required = isOther;
            if (!isOther) {
                fundingOtherInput.value = '';
            }
        };

        fundingSourceSelect.addEventListener('change', toggleFundingOther);
        toggleFundingOther();
    }
});

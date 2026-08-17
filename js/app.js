// Správa globálního stavu aplikace
const App = {
    user: null,         // Aktuálně přihlášený uživatel
    users: [],          // Seznam uživatelů (pouze pro filtry nebo pro administrátora)
    shifts: [],         // Směny aktuálně zobrazeného měsíce/zaměstnance
    filters: {
        employee_id: 0,
        month: new Date().getMonth() + 1,
        year: new Date().getFullYear()
    },
    activeTab: 'shifts', // 'shifts' | 'bulk' | 'users'

    async init() {
        this.setupEventListeners();
        await this.checkAuth();
    },

    setupEventListeners() {
        // Přihlašovací formulář
        document.getElementById('login-form')?.addEventListener('submit', async (e) => {
            e.preventDefault();
            const email = document.getElementById('login-email').value;
            const heslo = document.getElementById('login-password').value;
            await this.login(email, heslo);
        });

        // Tlačítko odhlášení
        document.getElementById('logout-btn')?.addEventListener('click', () => this.logout());

        // Přepínání záložek (tabs)
        document.getElementById('tab-shifts')?.addEventListener('click', () => this.switchTab('shifts'));
        document.getElementById('tab-bulk')?.addEventListener('click', () => this.switchTab('bulk'));
        document.getElementById('tab-users')?.addEventListener('click', () => this.switchTab('users'));

        // Filtry směn
        document.getElementById('filter-employee')?.addEventListener('change', (e) => {
            this.filters.employee_id = parseInt(e.target.value) || 0;
            this.loadShifts();
        });
        document.getElementById('filter-month')?.addEventListener('change', (e) => {
            this.filters.month = parseInt(e.target.value) || 1;
            this.loadShifts();
        });
        document.getElementById('filter-year')?.addEventListener('change', (e) => {
            this.filters.year = parseInt(e.target.value) || 2026;
            this.loadShifts();
        });

        // PDF generátor tlačítko
        document.getElementById('pdf-btn')?.addEventListener('click', () => {
            const url = `/generate-pdf?employee_id=${this.filters.employee_id}&month=${this.filters.month}&year=${this.filters.year}`;
            window.open(url, '_blank');
        });

        // Formulář pro novou směnu (Modal)
        document.getElementById('add-shift-btn')?.addEventListener('click', () => this.openShiftModal());
        document.getElementById('shift-modal-form')?.addEventListener('submit', (e) => this.saveShift(e));

        // Formulář pro nového uživatele (Modal)
        document.getElementById('add-user-btn')?.addEventListener('click', () => this.openUserModal());
        document.getElementById('user-modal-form')?.addEventListener('submit', (e) => this.saveUser(e));
    },

    async checkAuth() {
        try {
            const data = await API.get('/api/auth/me');
            API.setCsrfToken(data.csrf_token);
            if (data.logged_in) {
                this.user = data.user;
                this.showApp();
            } else {
                this.showLogin();
            }
        } catch (error) {
            this.showLogin();
        }
    },

    async login(email, heslo) {
        this.clearAlerts();
        try {
            const data = await API.post('/api/auth/login', { email, heslo });
            this.user = data.user;
            this.showApp();
        } catch (error) {
            this.showAlert('login-alert', error.message, 'danger');
        }
    },

    async logout() {
        try {
            await API.post('/api/auth/logout');
        } catch (e) {
            // Ignorujeme případnou chybu při logoutu
        }
        this.user = null;
        this.showLogin();
    },

    showLogin() {
        document.getElementById('auth-layout').style.display = 'flex';
        document.getElementById('app-layout').style.display = 'none';
        document.getElementById('login-password').value = '';
    },

    async showApp() {
        document.getElementById('auth-layout').style.display = 'none';
        document.getElementById('app-layout').style.display = 'block';
        document.getElementById('user-display-name').innerText = this.user.jmeno + ' ' + this.user.prijmeni;

        // Skrýt administrátorské záložky/tlačítka pro běžné zaměstnance
        const adminElements = document.querySelectorAll('.admin-only');
        adminElements.forEach(el => {
            el.style.display = this.user.role === 'admin' ? 'inline-block' : 'none';
        });

        // Načteme uživatele do filtrů
        await this.loadUsers();

        // Pokud nejsme admin, pevně nastavíme filtr na přihlášeného uživatele
        if (this.user.role !== 'admin') {
            this.filters.employee_id = this.user.id;
            document.getElementById('filter-employee-container').style.display = 'none';
        } else {
            document.getElementById('filter-employee-container').style.display = 'block';
            if (this.filters.employee_id === 0) {
                this.filters.employee_id = this.user.id;
            }
        }

        // Nastavíme hodnoty filtrů v UI
        document.getElementById('filter-employee').value = this.filters.employee_id;
        document.getElementById('filter-month').value = this.filters.month;
        document.getElementById('filter-year').value = this.filters.year;

        // Načteme výchozí záložku
        this.switchTab(this.activeTab);
    },

    async loadUsers() {
        try {
            const data = await API.get('/api/users');
            this.users = data.users;
            this.renderUserFilters();
            if (this.activeTab === 'users' && this.user.role === 'admin') {
                this.renderUsersTable();
            }
        } catch (error) {
            console.error("Chyba při načítání uživatelů", error);
        }
    },

    renderUserFilters() {
        const select = document.getElementById('filter-employee');
        if (!select) return;
        
        // Uchováme aktuální výběr
        const currentVal = select.value || this.filters.employee_id;
        
        select.innerHTML = '';
        this.users.forEach(u => {
            const opt = document.createElement('option');
            opt.value = u.id;
            opt.innerText = `${u.prijmeni}, ${u.jmeno}`;
            select.appendChild(opt);
        });

        select.value = currentVal;
    },

    async switchTab(tabName) {
        this.activeTab = tabName;
        
        // Vizualizace tlačítek
        document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
        document.getElementById(`tab-${tabName}`)?.classList.add('active');

        // Zobrazení sekcí
        document.getElementById('section-shifts').style.display = tabName === 'shifts' ? 'block' : 'none';
        document.getElementById('section-bulk').style.display = tabName === 'bulk' ? 'block' : 'none';
        document.getElementById('section-users').style.display = tabName === 'users' ? 'block' : 'none';

        if (tabName === 'shifts') {
            await this.loadShifts();
        } else if (tabName === 'bulk') {
            await this.initBulkEditor();
        } else if (tabName === 'users') {
            if (this.user.role !== 'admin') {
                this.switchTab('shifts');
                return;
            }
            await this.loadUsers();
        }
    },

    async loadShifts() {
        this.clearAlerts();
        try {
            const data = await API.get(`/api/shifts?employee_id=${this.filters.employee_id}&month=${this.filters.month}&year=${this.filters.year}`);
            this.shifts = data.shifts;
            
            // Zachováme filtry zaslané serverem
            this.filters.employee_id = data.filters.employee_id;
            this.filters.month = data.filters.month;
            this.filters.year = data.filters.year;

            this.renderShiftsTable();
        } catch (error) {
            this.showAlert('app-alert', error.message, 'danger');
        }
    },

    renderShiftsTable() {
        const tbody = document.getElementById('shifts-tbody');
        if (!tbody) return;

        tbody.innerHTML = '';

        if (this.shifts.length === 0) {
            tbody.innerHTML = `<tr><td colspan="8" style="text-align: center; color: var(--text-muted);">Nebyly nalezeny žádné směny pro vybrané období.</td></tr>`;
            return;
        }

        this.shifts.forEach(s => {
            const tr = document.createElement('tr');
            
            // Detekce víkendu
            const dateObj = new Date(s.datum);
            const isWeekend = dateObj.getDay() === 0 || dateObj.getDay() === 6;
            if (isWeekend) {
                tr.classList.add('weekend-row');
            }

            const formattedDate = new Date(s.datum).toLocaleDateString('cs-CZ');
            const startTime = s.cas_zacatku.substring(0, 5);
            const endTime = s.cas_konce.substring(0, 5);
            const hoursFormatted = s.celkem_hodin.toFixed(2).replace('.', ',');

            tr.innerHTML = `
                <td>${s.id}</td>
                <td>${s.prijmeni}, ${s.jmeno}</td>
                <td><strong>${formattedDate}</strong></td>
                <td>${startTime}</td>
                <td>${endTime}</td>
                <td><strong>${hoursFormatted} hod.</strong></td>
                <td>${s.poznamka || ''}</td>
                <td>${s.noni == 1 ? 'Ano' : 'Ne'}</td>
                <td>
                    <div style="display: flex; gap: 5px;">
                        <button class="btn btn-secondary btn-sm edit-shift-btn" data-id="${s.id}">Upravit</button>
                        <button class="btn btn-danger btn-sm delete-shift-btn" data-id="${s.id}">Smazat</button>
                    </div>
                </td>
            `;

            // Listenery pro editaci a smazání
            tr.querySelector('.edit-shift-btn').addEventListener('click', () => this.openShiftModal(s));
            tr.querySelector('.delete-shift-btn').addEventListener('click', () => this.deleteShift(s.id));

            tbody.appendChild(tr);
        });
    },

    // --- BULK EDITOR ---
    async initBulkEditor() {
        this.clearAlerts();
        // Načteme aktuální směny pro měsíc, abychom předvyplnili data
        try {
            const data = await API.get(`/api/shifts?employee_id=${this.filters.employee_id}&month=${this.filters.month}&year=${this.filters.year}`);
            const existingShifts = data.shifts;
            
            // Sestavení mapy datum -> směna
            const shiftMap = {};
            existingShifts.forEach(s => {
                shiftMap[s.datum] = s;
            });

            // Vygenerování řádků pro všechny dny v měsíci
            const daysInMonth = new Date(this.filters.year, this.filters.month, 0).getDate();
            const tbody = document.getElementById('bulk-tbody');
            tbody.innerHTML = '';

            for (let day = 1; day <= daysInMonth; day++) {
                const dateStr = `${this.filters.year}-${String(this.filters.month).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
                const shift = shiftMap[dateStr] || null;

                const tr = document.createElement('tr');
                
                // Určení dne v týdnu
                const tempDate = new Date(this.filters.year, this.filters.month - 1, day);
                const dayOfWeek = tempDate.getDay();
                const isWeekend = dayOfWeek === 0 || dayOfWeek === 6;
                const daysCs = ['Neděle', 'Pondělí', 'Úterý', 'Středa', 'Čtvrtek', 'Pátek', 'Sobota'];

                if (isWeekend) {
                    tr.classList.add('weekend-row');
                }

                tr.innerHTML = `
                    <td><strong>${day}. (${daysCs[dayOfWeek]})</strong></td>
                    <td>
                        <input type="time" class="bulk-start" data-date="${dateStr}" value="${shift ? shift.cas_zacatku.substring(0, 5) : ''}">
                    </td>
                    <td>
                        <input type="time" class="bulk-end" data-date="${dateStr}" value="${shift ? shift.cas_konce.substring(0, 5) : ''}">
                    </td>
                    <td>
                        <input type="checkbox" class="bulk-noni" data-date="${dateStr}" ${shift && shift.noni == 1 ? 'checked' : ''}>
                    </td>
                    <td>
                        <input type="text" class="bulk-note" data-date="${dateStr}" placeholder="Poznámka..." value="${shift ? shift.poznamka || '' : ''}">
                    </td>
                `;

                tbody.appendChild(tr);
            }

            // Nastavení listeneru na uložení celého měsíce
            const saveBtn = document.getElementById('bulk-save-btn');
            // Naklonujeme tlačítko pro odstranění předchozích listenerů
            const newSaveBtn = saveBtn.cloneNode(true);
            saveBtn.parentNode.replaceChild(newSaveBtn, saveBtn);

            newSaveBtn.addEventListener('click', () => this.saveBulkData());

        } catch (error) {
            this.showAlert('app-alert', error.message, 'danger');
        }
    },

    async saveBulkData() {
        this.clearAlerts();
        const tbody = document.getElementById('bulk-tbody');
        const rows = tbody.querySelectorAll('tr');
        const shiftsToSave = [];

        rows.forEach(tr => {
            const startInput = tr.querySelector('.bulk-start');
            const endInput = tr.querySelector('.bulk-end');
            const noniInput = tr.querySelector('.bulk-noni');
            const noteInput = tr.querySelector('.bulk-note');

            const datum = startInput.dataset.date;
            const cas_zacatku = startInput.value;
            const cas_konce = endInput.value;
            const noni = noniInput.checked;
            const poznamka = noteInput.value;

            // Uložíme pouze pokud je vyplněný aspoň jeden čas (buď oba, nebo pokud chce smazat, tak prázdné)
            // Backend při obou prázdných smaže existující záznam pro tento den
            shiftsToSave.push({
                datum,
                cas_zacatku,
                cas_konce,
                noni,
                poznamka
            });
        });

        try {
            const response = await API.post('/api/shifts/bulk', {
                employee_id: this.filters.employee_id,
                shifts: shiftsToSave
            });
            this.showAlert('app-alert', response.message, 'success');
            await this.switchTab('shifts'); // Přepnout na seznam a aktualizovat
        } catch (error) {
            this.showAlert('app-alert', error.message, 'danger');
        }
    },

    // --- SHIFT SINGLE CRUD (MODAL) ---
    openShiftModal(shift = null) {
        const modal = document.getElementById('shift-modal');
        const form = document.getElementById('shift-modal-form');
        const title = document.getElementById('shift-modal-title');

        form.reset();

        // Předvyplníme zaměstnance z filtrů
        const employeeSelect = document.getElementById('shift-employee');
        employeeSelect.innerHTML = '';
        this.users.forEach(u => {
            const opt = document.createElement('option');
            opt.value = u.id;
            opt.innerText = `${u.prijmeni}, ${u.jmeno}`;
            employeeSelect.appendChild(opt);
        });

        // Obyčejný zaměstnanec vidí v selectu jen sebe a nemůže měnit
        if (this.user.role !== 'admin') {
            employeeSelect.value = this.user.id;
            employeeSelect.disabled = true;
        } else {
            employeeSelect.disabled = false;
            employeeSelect.value = this.filters.employee_id || this.user.id;
        }

        if (shift) {
            title.innerText = 'Upravit směnu';
            document.getElementById('shift-id').value = shift.id;
            document.getElementById('shift-employee').value = shift.id_zamestnance;
            document.getElementById('shift-date').value = shift.datum;
            document.getElementById('shift-start').value = shift.cas_zacatku.substring(0, 5);
            document.getElementById('shift-end').value = shift.cas_konce.substring(0, 5);
            document.getElementById('shift-note').value = shift.poznamka || '';
            document.getElementById('shift-noni').checked = shift.noni == 1;
        } else {
            title.innerText = 'Přidat novou směnu';
            document.getElementById('shift-id').value = '';
            document.getElementById('shift-date').value = new Date().toISOString().substring(0, 10);
            document.getElementById('shift-start').value = '07:00';
            document.getElementById('shift-end').value = '19:00';
            document.getElementById('shift-noni').checked = false;
        }

        modal.style.display = 'flex';

        // Zavření kliknutím na křížek nebo mimo modal
        const closeBtn = modal.querySelector('.modal-close');
        closeBtn.onclick = () => modal.style.display = 'none';
        window.onclick = (e) => {
            if (e.target === modal) modal.style.display = 'none';
        };
    },

    async saveShift(e) {
        e.preventDefault();
        this.clearAlerts();

        const id = document.getElementById('shift-id').value;
        const payload = {
            id_zamestnance: parseInt(document.getElementById('shift-employee').value),
            datum: document.getElementById('shift-date').value,
            cas_zacatku: document.getElementById('shift-start').value,
            cas_konce: document.getElementById('shift-end').value,
            poznamka: document.getElementById('shift-note').value,
            noni: document.getElementById('shift-noni').checked
        };

        try {
            let res;
            if (id) {
                res = await API.put(`/api/shifts/${id}`, payload);
            } else {
                res = await API.post('/api/shifts', payload);
            }

            document.getElementById('shift-modal').style.display = 'none';
            this.showAlert('app-alert', res.message, 'success');
            
            // Zachovat měsíc a rok z nově přidané/upravené směny pro přehlednost
            const shiftDate = new Date(payload.datum);
            this.filters.month = shiftDate.getMonth() + 1;
            this.filters.year = shiftDate.getFullYear();
            if (this.user.role === 'admin') {
                this.filters.employee_id = payload.id_zamestnance;
            }

            // Aktualizace filtrů v UI
            document.getElementById('filter-month').value = this.filters.month;
            document.getElementById('filter-year').value = this.filters.year;
            document.getElementById('filter-employee').value = this.filters.employee_id;

            await this.loadShifts();
        } catch (error) {
            alert(error.message);
        }
    },

    async deleteShift(id) {
        if (!confirm("Opravdu chcete smazat tuto směnu?")) return;
        this.clearAlerts();

        try {
            const res = await API.delete(`/api/shifts/${id}`);
            this.showAlert('app-alert', res.message, 'success');
            await this.loadShifts();
        } catch (error) {
            this.showAlert('app-alert', error.message, 'danger');
        }
    },

    // --- USER MANAGEMENT (ADMIN ONLY) ---
    renderUsersTable() {
        const tbody = document.getElementById('users-tbody');
        if (!tbody) return;

        tbody.innerHTML = '';

        this.users.forEach(u => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${u.id}</td>
                <td>${u.prijmeni}</td>
                <td>${u.jmeno}</td>
                <td>${u.email}</td>
                <td>${u.telefon || ''}</td>
                <td><span class="badge badge-${u.role}">${u.role === 'admin' ? 'Administrátor' : 'Zaměstnanec'}</span></td>
                <td>
                    <div style="display: flex; gap: 5px;">
                        <button class="btn btn-secondary btn-sm edit-user-btn" data-id="${u.id}">Upravit</button>
                        <button class="btn btn-danger btn-sm delete-user-btn" data-id="${u.id}">Smazat</button>
                    </div>
                </td>
            `;

            tr.querySelector('.edit-user-btn').addEventListener('click', () => this.openUserModal(u));
            tr.querySelector('.delete-user-btn').addEventListener('click', () => this.deleteUser(u.id));

            tbody.appendChild(tr);
        });
    },

    openUserModal(user = null) {
        const modal = document.getElementById('user-modal');
        const form = document.getElementById('user-modal-form');
        const title = document.getElementById('user-modal-title');

        form.reset();
        document.getElementById('user-password-help').style.display = 'none';

        if (user) {
            title.innerText = 'Upravit uživatele';
            document.getElementById('user-id').value = user.id;
            document.getElementById('user-firstname').value = user.jmeno;
            document.getElementById('user-lastname').value = user.prijmeni;
            document.getElementById('user-email').value = user.email;
            document.getElementById('user-phone').value = user.telefon || '';
            document.getElementById('user-role').value = user.role;
            document.getElementById('user-password').required = false;
            document.getElementById('user-password-help').style.display = 'block';
        } else {
            title.innerText = 'Přidat nového uživatele';
            document.getElementById('user-id').value = '';
            document.getElementById('user-role').value = 'employee';
            document.getElementById('user-password').required = true;
        }

        modal.style.display = 'flex';

        const closeBtn = modal.querySelector('.modal-close');
        closeBtn.onclick = () => modal.style.display = 'none';
        window.onclick = (e) => {
            if (e.target === modal) modal.style.display = 'none';
        };
    },

    async saveUser(e) {
        e.preventDefault();
        this.clearAlerts();

        const id = document.getElementById('user-id').value;
        const payload = {
            jmeno: document.getElementById('user-firstname').value,
            prijmeni: document.getElementById('user-lastname').value,
            email: document.getElementById('user-email').value,
            telefon: document.getElementById('user-phone').value,
            role: document.getElementById('user-role').value
        };

        const password = document.getElementById('user-password').value;
        if (password) {
            payload.heslo = password;
        }

        try {
            let res;
            if (id) {
                res = await API.put(`/api/users/${id}`, payload);
            } else {
                res = await API.post('/api/users', payload);
            }

            document.getElementById('user-modal').style.display = 'none';
            this.showAlert('app-alert', res.message, 'success');
            await this.loadUsers();
        } catch (error) {
            alert(error.message);
        }
    },

    async deleteUser(id) {
        if (id === this.user.id) {
            alert("Nemůžete smazat svůj vlastní účet!");
            return;
        }

        if (!confirm("Opravdu chcete smazat tohoto uživatele?")) return;
        this.clearAlerts();

        try {
            const res = await API.delete(`/api/users/${id}`);
            this.showAlert('app-alert', res.message, 'success');
            await this.loadUsers();
        } catch (error) {
            this.showAlert('app-alert', error.message, 'danger');
        }
    },

    // --- ALERTS HELPER ---
    showAlert(containerId, message, type = 'success') {
        const container = document.getElementById(containerId);
        if (!container) return;

        container.innerHTML = `
            <div class="alert alert-${type}">
                ${message}
            </div>
        `;
    },

    clearAlerts() {
        const alerts = ['login-alert', 'app-alert'];
        alerts.forEach(id => {
            const el = document.getElementById(id);
            if (el) el.innerHTML = '';
        });
    }
};

// Spuštění aplikace po načtení stránky
document.addEventListener('DOMContentLoaded', () => {
    App.init();
});

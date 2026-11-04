const clean = value => typeof value === 'string' || typeof value === 'number' ? String(value).trim() : '';

const phoneNumber = value => {
    const display = clean(value);
    // Conserva il formato leggibile; il link ammette solo una destinazione telefonica.
    if (!display || !/^[+\d\s()./-]+$/.test(display)) return null;
    const dial = display.replace(/[\s()./-]/g, '');
    return /^\+?\d{3,}$/.test(dial) ? { display, href: `tel:${dial}` } : null;
};

const contactRoles = {
    smaltimenti: {icon: 'user-tie', label: 'Responsabile smaltimenti'},
    segreteria: {icon: 'headset', label: 'Segreteria'},
    magazzino: {icon: 'warehouse', label: 'Magazzino'},
    inrtermediario: {icon: 'handshake', label: 'Intermediario'},
};

export const siteContactRole = value => Object.hasOwn(contactRoles, clean(value))
    ? contactRoles[clean(value)] : {icon: 'user', label: 'Ruolo non specificato'};

export const siteContactRows = contacts => {
    const rows = (Array.isArray(contacts) ? contacts : []).filter(contact => contact && typeof contact === 'object');
    return (rows.length ? rows : [{}]).map((contact, index) => {
        const mobile = phoneNumber(contact.mobile), landline = phoneNumber(contact.phone);
        const numbers = [mobile && {...mobile, label:'Cell.', icon:'mobile-screen-button'}, landline && {...landline, label:'Tel.', icon:'phone'}]
            .filter(Boolean).filter((number, index, all) => all.findIndex(other => other.href === number.href) === index);
        const phone = numbers[0];
        const role = siteContactRole(contact.role);
        return {
            numbers,
            roleIcon: role.icon,
            roleLabel: role.label,
            key: contact.id ?? index,
            name: [clean(contact.name), clean(contact.surname)].filter(Boolean).join(' ') || 'nessun referente',
            number: phone?.display ?? 'nessun numero',
            href: phone?.href ?? null,
        };
    });
};

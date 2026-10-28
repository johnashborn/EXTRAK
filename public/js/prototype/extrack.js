/*
 * EXTRACK prototype: Extension Director and Campus Director dashboards.
 * Front-end only. Mock data lives in memory and is saved to localStorage so changes survive a reload,
 * and both dashboards read the same store. Mirrors docs/spec.md v0.3.
 */
(function () {
  const STORE_KEY = 'extrack.proto.v7';
  const TODAY = '2026-10-28'; // fixed so overdue and upcoming logic stays consistent with the mock data
  const YEAR = 2026;
  const CAMPUSES = ['Main', 'Balilihan', 'Bilar', 'Calape', 'Candijay', 'Clarin'];
  // yearly extension budget per campus (set by the System Admin in the real system)
  const ALLOCATIONS = { Main: 900000, Balilihan: 500000, Bilar: 450000, Calape: 450000, Candijay: 400000, Clarin: 400000 };
  // town centres in Bohol; barangay pins are placed around them (approximate, not surveyed positions)
  const TOWNS = {
    Balilihan: [9.7553, 123.9703], Tagbilaran: [9.6476, 123.8550], Calape: [9.8918, 123.8768], Tubigon: [9.9524, 123.9625],
    Loon: [9.7990, 123.8000], Bilar: [9.7095, 124.1083], Candijay: [9.8186, 124.4958], Clarin: [9.9617, 124.0247],
    Catigbian: [9.8317, 124.0300], Sevilla: [9.7074, 124.0515],
  };
  const CAMPUS_COLORS = { Main: '#3A3272', Balilihan: '#0F7A52', Bilar: '#2B5CAB', Calape: '#B25E09', Candijay: '#8E3B8C', Clarin: '#127C86' };
  function hashOf(s) { let h = 0; for (const ch of s) h = (h * 31 + ch.charCodeAt(0)) | 0; return Math.abs(h); }
  function placeOf(municipality, barangay) {
    const c = TOWNS[municipality] || TOWNS.Balilihan;
    const h = hashOf(municipality + '/' + barangay);
    const angle = (h % 360) * Math.PI / 180, radius = 0.006 + ((h >> 9) % 100) / 100 * 0.018;
    return [c[0] + Math.sin(angle) * radius, c[1] + Math.cos(angle) * radius];
  }

  // supporting documents attached to a request
  const DOC_KINDS = { letter: 'Barangay letter of request', proposal: 'Project proposal', budget: 'Budget breakdown', moa: 'MOA or partnership letter', other: 'Other document' };
  // the approvers are warned when these are missing (spec 3.2, [Open]: confirm the list)
  const REQUIRED_DOCS = ['letter', 'proposal'];
  const DOC_LIMIT = 1024 * 1024; // prototype only: files live in browser storage
  function guessKind(name) {
    const n = name.toLowerCase();
    if (/letter|request|endorse/.test(n)) return 'letter';
    if (/proposal|plan|design/.test(n)) return 'proposal';
    if (/budget|cost|lib\b/.test(n)) return 'budget';
    if (/moa|mou|agreement|partner/.test(n)) return 'moa';
    return 'other';
  }
  function readAsDataUrl(file) {
    return new Promise((resolve, reject) => {
      const fr = new FileReader();
      fr.onload = () => resolve(fr.result);
      fr.onerror = () => reject(fr.error);
      fr.readAsDataURL(file);
    });
  }

  // older sample records have no campus field; they all belong to Balilihan
  const campusOf = r => r.campus || 'Balilihan';

  const PEOPLE = {
    ed: { name: 'Ma. Elena Racines', title: 'Extension Director', initials: 'ER' },
    asst: { name: 'Joy Cruz', title: "Extension Director's Assistant", initials: 'JC' },
    cd: { name: 'Dr. Ramon Tabada', title: 'Campus Director', initials: 'RT' },
    cdasst: { name: 'Lorna Abella', title: "Campus Director's Assistant", initials: 'LA' },
    pres: { name: 'University President', title: 'University President', initials: 'UP' },
  };
  // the other campuses' directors only appear in history entries
  CAMPUSES.filter(c => c !== 'Balilihan').forEach(c => {
    PEOPLE[c + '-ed'] = { name: c + ' Campus Extension Director', title: 'Extension Director', initials: c.slice(0, 2).toUpperCase() };
    PEOPLE[c + '-cd'] = { name: c + ' Campus Director', title: 'Campus Director', initials: c.slice(0, 2).toUpperCase() };
  });

  // The signed-in account plays the role of the page it opened (set by the Blade view)
  if (window.EXTRACK_USER && window.EXTRACK_USER.name) {
    const name = window.EXTRACK_USER.name;
    const initials = name.split(/\s+/).filter(Boolean).slice(0, 2).map(w => w[0].toUpperCase()).join('');
    const role = { cd: 'cd', pres: 'pres' }[window.EXTRACK_ROLE] || 'ed';
    PEOPLE[role] = { ...PEOPLE[role], name, initials };
  }

  const CATEGORIES = ['Agriculture & Fisheries', 'Livelihood', 'Health', 'Education', 'Environment', 'Technology & Digital', 'Others'];
  const SECTORS = ['Fisherfolk', 'Farmers', 'Women', 'Youth', 'Senior citizens', 'PWD', '4Ps households', 'Teachers', 'Health workers', 'MSME owners'];
  const PLACES = {
    Balilihan: ['Baucan Norte', 'Baucan Sur', 'Boctol', 'Cabad', 'Cantomimbo', 'Cantoyoc', 'Datag Norte', 'Datag Sur', 'Del Carmen Sur', 'Dorol', 'Haguilanan Grande', 'Hanopol Norte', 'Magsija', 'Maslog', 'Sagasa', 'Sal-ing', 'San Roque'],
    Catigbian: ['Ambuan', 'Bagtic', 'Bongbong', 'Hagbuaya', 'Mahayag Norte'],
    Sevilla: ['Bayawahan', 'Calinga-an', 'Lagtangon', 'Magsaysay'],
  };

  const STATUS = {
    draft: { label: 'Draft', tone: 'muted' },
    submitted: { label: 'Waiting for campus approval', tone: 'wait' },
    for_president: { label: 'With the President', tone: 'wait' },
    returned: { label: 'Returned', tone: 'warn' },
    declined: { label: 'Declined', tone: 'bad' },
    approved: { label: 'Approved', tone: 'ok' },
    ongoing: { label: 'Ongoing', tone: 'live' },
    completed: { label: 'Completed', tone: 'done' },
    archived: { label: 'Archived', tone: 'muted' },
  };

  // Keyword hints for the suggested category
  const CATEGORY_HINTS = {
    'Agriculture & Fisheries': ['farm', 'farming', 'seaweed', 'aquaculture', 'fish', 'fisherfolk', 'crop', 'vegetable', 'organic', 'rice', 'livestock', 'compost', 'vermicompost', 'garden', 'tilapia', 'catfish', 'pond', 'hog', 'poultry', 'pest'],
    Livelihood: ['livelihood', 'income', 'enterprise', 'handicraft', 'weaving', 'bookkeeping', 'store', 'sari-sari', 'processing', 'product', 'entrepreneur', 'msme', 'sewing'],
    Health: ['health', 'nutrition', 'feeding', 'first aid', 'medical', 'dental', 'wellness', 'blood', 'diabetes', 'sanitation', 'hygiene', 'mental'],
    Education: ['reading', 'literacy', 'learners', 'school', 'tutorial', 'teacher', 'numeracy', 'remediation', 'grade'],
    Environment: ['mangrove', 'coastal', 'cleanup', 'waste', 'tree', 'climate', 'disaster', 'reef', 'watershed', 'recycling'],
    'Technology & Digital': ['digital', 'computer', 'internet', 'online', 'smartphone', 'ict', 'software', 'app'],
  };

  // Words treated as the same theme by the overlap check (stand-in for semantic similarity)
  const THEMES = [
    ['seaweed', 'aquaculture', 'fish', 'fisherfolk', 'tilapia', 'catfish', 'pond', 'mariculture', 'fishing'],
    ['tree', 'planting', 'watershed', 'reforestation', 'riverbank'],
    ['farm', 'farming', 'agriculture', 'crop', 'garden', 'gardening', 'vegetable', 'organic'],
    ['income', 'livelihood', 'enterprise', 'entrepreneur'],
    ['mangrove', 'coastal', 'shoreline'],
    ['reading', 'literacy', 'learner', 'learners', 'remediation'],
    ['health', 'wellness', 'medical'],
    ['digital', 'computer', 'ict', 'smartphone'],
    ['compost', 'composting', 'vermicompost', 'vermicomposting'],
  ];
  const STOP = new Set('a an and the for of to in on at by with from into is are be this that these those will who what how their our its as or it we they them more about through via per each new'.split(' '));

  // Other campuses' work: the overlap check only shows this limited summary (spec 2.2)
  const OTHER_CAMPUSES = [
    { code: 'MAIN-2026-031', campus: 'Main Campus', title: 'Freshwater Aquaculture Seminar for Farming Families', description: 'Seminar on tilapia and catfish pond culture for farming families, focused on household income.', municipality: 'Balilihan', barangays: ['Cabad'], start: '2026-10-05', end: '2026-12-10', status: 'for_president' },
    { code: 'CLA-2026-016', campus: 'Clarin Campus', title: 'Mangrove Nursery Establishment', description: 'Set up a community mangrove nursery and train volunteers on coastal planting.', municipality: 'Tubigon', barangays: ['Matabao'], start: '2026-11-03', end: '2026-12-15', status: 'approved' },
    { code: 'BIL-2026-020', campus: 'Bilar Campus', title: 'Organic Vegetable Gardening and Composting for Households', description: 'Household organic vegetable gardens and composting for food security.', municipality: 'Balilihan', barangays: ['Datag Norte'], start: '2026-08-15', end: '2026-12-15', status: 'ongoing' },
    { code: 'BIL-2026-009', campus: 'Bilar Campus', title: 'Reading Intervention Program for Grade 3 Learners', description: 'Reading remediation sessions for struggling grade 3 learners with parent coaching.', municipality: 'Loon', barangays: ['Napo'], start: '2026-07-01', end: '2026-11-30', status: 'ongoing' },
    { code: 'MAIN-2026-005', campus: 'Main Campus', title: 'Digital Literacy for Senior Citizens', description: 'Smartphone and online safety basics for senior citizens.', municipality: 'Tubigon', barangays: ['Centro'], start: '2026-02-01', end: '2026-04-30', status: 'completed' },
    { code: 'CAL-2026-035', campus: 'Calape Campus', title: 'Tree Planting along the Abatan River Watershed', description: 'Native tree planting and riverbank protection along the Abatan River with youth volunteers.', municipality: 'Balilihan', barangays: ['Magsija'], start: '2026-11-10', end: '2026-11-12', status: 'submitted' },
  ];

  // Labelled placeholder images for the sample reports; real reports keep the photos that were uploaded
  function samplePhoto(caption, hue) {
    const svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 800 600">'
      + '<defs><linearGradient id="g" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="hsl(' + hue + ',42%,70%)"/><stop offset="1" stop-color="hsl(' + (hue + 30) + ',38%,46%)"/></linearGradient></defs>'
      + '<rect width="800" height="600" fill="url(#g)"/>'
      + '<g fill="none" stroke="rgba(255,255,255,.75)" stroke-width="10" stroke-linejoin="round"><rect x="310" y="200" width="180" height="130" rx="18"/><circle cx="400" cy="265" r="38"/><path d="M350 200l18-28h64l18 28"/></g>'
      + '<text x="400" y="420" text-anchor="middle" font-family="Segoe UI,Arial,sans-serif" font-size="30" fill="#fff">' + caption + '</text>'
      + '<text x="400" y="462" text-anchor="middle" font-family="Segoe UI,Arial,sans-serif" font-size="22" fill="rgba(255,255,255,.8)">Sample photo</text></svg>';
    return { name: caption, src: 'data:image/svg+xml;charset=utf-8,' + encodeURIComponent(svg) };
  }

  // Resize a photo before keeping it, so several fit in browser storage
  function shrinkImage(file, max = 1000) {
    return new Promise((resolve, reject) => {
      const url = URL.createObjectURL(file);
      const img = new Image();
      img.onload = () => {
        const k = Math.min(1, max / Math.max(img.width, img.height));
        const c = document.createElement('canvas');
        c.width = Math.round(img.width * k); c.height = Math.round(img.height * k);
        c.getContext('2d').drawImage(img, 0, 0, c.width, c.height);
        URL.revokeObjectURL(url);
        resolve(c.toDataURL('image/jpeg', 0.72));
      };
      img.onerror = () => { URL.revokeObjectURL(url); reject(new Error('unreadable')); };
      img.src = url;
    });
  }

  // Labelled placeholder pages for the sample requests' documents
  function sampleDoc(kind, name) {
    const lines = Array.from({ length: 9 }, (_, i) => '<rect x="90" y="' + (260 + i * 38) + '" width="' + (420 - (i % 3) * 70) + '" height="12" rx="6" fill="#D9D8E3"/>').join('');
    const svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 600 800"><rect width="600" height="800" fill="#fff"/>'
      + '<rect x="90" y="90" width="120" height="14" rx="7" fill="#3A3272"/>'
      + '<text x="90" y="170" font-family="Segoe UI,Arial,sans-serif" font-size="30" font-weight="700" fill="#17152B">' + DOC_KINDS[kind] + '</text>'
      + '<text x="90" y="210" font-family="Segoe UI,Arial,sans-serif" font-size="18" fill="#6B6884">' + name + '</text>' + lines
      + '<text x="300" y="730" text-anchor="middle" font-family="Segoe UI,Arial,sans-serif" font-size="20" fill="#9A97B0">Sample document</text></svg>';
    return { id: 'd' + hashOf(kind + name), kind, name, type: 'image/svg+xml', size: 90000 + hashOf(name) % 400000, src: 'data:image/svg+xml;charset=utf-8,' + encodeURIComponent(svg), sample: true, by: 'ed', at: '' };
  }
  // documents for the sample requests, besides the barangay letters already named in the data
  const SAMPLE_DOCS = {
    r14: [['proposal', 'Tilapia_training_proposal.pdf']],
    r19: [['proposal', 'Watershed_rehabilitation_proposal.pdf'], ['moa', 'DENR_partnership_letter.pdf']],
    r21: [['proposal', 'Bookkeeping_workshop_proposal.pdf']],
    r22: [['proposal', 'Digital_literacy_proposal.pdf']],
    r23: [['proposal', 'Organic_farming_program_proposal.pdf'], ['budget', 'Organic_farming_budget.xlsx']],
    r12: [['letter', 'Hanopol_Norte_request.pdf'], ['proposal', 'Reading_remediation_plan.pdf']],
    r15: [['letter', 'Saling_senior_citizens_request.pdf'], ['proposal', 'Wellness_program_proposal.pdf']],
    r24: [['proposal', 'WASH_seminar_outline.docx']],
    r11: [['letter', 'Baucan_Norte_request.pdf'], ['proposal', 'Coco_fiber_training_proposal.pdf']],
    r08: [['letter', 'Cabad_request.pdf'], ['proposal', 'Meat_processing_proposal.pdf']],
    r31: [['letter', 'Del_Carmen_Sur_request.pdf'], ['proposal', 'IPM_orientation_proposal.pdf']],
    'cla-2026-021': [['letter', 'Bonbon_request.pdf'], ['proposal', 'Rice_duck_demo_proposal.pdf'], ['budget', 'Rice_duck_budget.xlsx']],
    'can-2026-028': [['letter', 'Cogtong_request.pdf'], ['proposal', 'Mat_weaving_proposal.pdf']],
    'main-2026-033': [['proposal', 'MHFA_training_proposal.pdf']],
    'cal-2026-035': [['letter', 'Magsija_request.pdf'], ['proposal', 'Tree_planting_proposal.pdf']],
  };

  function ev(at, by, from, to, remark) { return { at, by, from, to, remark: remark || '' }; }

  // history from submission up to a status, by the campus's own directors and the President
  function trail(c, upTo, dates, stop) {
    const who = { submitted: c + '-ed', for_president: c + '-cd', approved: 'pres', ongoing: c + '-ed', completed: c + '-ed' };
    const order = ['submitted', 'for_president', 'approved', 'ongoing', 'completed'];
    const events = [];
    let prev = null;
    order.slice(0, order.indexOf(upTo) + 1).forEach((to, i) => { events.push(ev(dates[i], who[to], prev, to)); prev = to; });
    if (stop) events.push(ev(stop.at, stop.by === 'pres' ? 'pres' : c + '-cd', prev, stop.to, stop.remark));
    return events;
  }
  function rec(c, code, f) {
    return Object.assign({ id: code.toLowerCase(), code, campus: c, requestedBy: c + '-ed', encodedBy: c + '-ed', partners: '', letter: '', overlap: null, remarks: [], report: null }, f);
  }

  // Sample work from the other five campuses, so the President sees the whole university
  function otherCampusRequests() {
    const O = OTHER_CAMPUSES;
    const base = o => ({ title: o.title, description: o.description, municipality: o.municipality, barangays: o.barangays, start: o.start, end: o.end });
    return [
      rec('Main', O[0].code, { ...base(O[0]), category: 'Agriculture & Fisheries', budget: 95000, beneficiaries: 45, sectors: ['Farmers'], status: 'ongoing',
        events: trail('Main', 'ongoing', ['2026-08-20T09:00', '2026-08-26T10:00', '2026-09-08T11:00', '2026-10-05T08:00']) }),
      rec('Clarin', O[1].code, { ...base(O[1]), category: 'Environment', budget: 70000, beneficiaries: 60, sectors: ['Youth', 'Fisherfolk'], status: 'approved',
        events: trail('Clarin', 'approved', ['2026-09-01T09:00', '2026-09-05T10:00', '2026-09-20T11:00']) }),
      rec('Bilar', O[2].code, { ...base(O[2]), category: 'Agriculture & Fisheries', budget: 80000, beneficiaries: 50, sectors: ['4Ps households'], status: 'ongoing',
        events: trail('Bilar', 'ongoing', ['2026-07-01T09:00', '2026-07-06T10:00', '2026-07-20T11:00', '2026-08-15T08:00']) }),
      rec('Bilar', O[3].code, { ...base(O[3]), category: 'Education', budget: 40000, beneficiaries: 35, sectors: ['Youth', 'Teachers'], status: 'ongoing',
        events: trail('Bilar', 'ongoing', ['2026-05-20T09:00', '2026-05-25T10:00', '2026-06-10T11:00', '2026-07-01T08:00']) }),
      rec('Main', O[4].code, { ...base(O[4]), category: 'Technology & Digital', budget: 50000, beneficiaries: 50, sectors: ['Senior citizens'], status: 'completed',
        events: trail('Main', 'completed', ['2025-12-10T09:00', '2025-12-15T10:00', '2026-01-08T11:00', '2026-02-01T08:00', '2026-05-06T15:00']),
        report: { actualStart: '2026-02-01', actualEnd: '2026-04-30', actualBudget: 47000, actualBeneficiaries: 52, narrative: '52 seniors can now video-call family and spot common online scams.', photos: [samplePhoto('Smartphone basics class', 280), samplePhoto('Seniors practising video calls', 190)] } }),
      rec('Calape', O[5].code, { ...base(O[5]), category: 'Environment', budget: 30000, beneficiaries: 80, sectors: ['Youth'], status: 'for_president',
        events: trail('Calape', 'for_president', ['2026-10-14T09:00', '2026-10-21T15:30']),
        overlap: { score: 74, match: { code: 'BAL-2026-019', campus: 'Balilihan Campus', title: 'Watershed Rehabilitation and Tree Planting', barangays: ['Magsija'], start: '2026-11-20', end: '2026-12-12' }, terms: ['tree', 'planting', 'watershed'], resolution: 'coordinate', resolvedBy: 'Calape-cd' } }),
      rec('Clarin', 'CLA-2026-021', { title: 'Rice-Duck Farming Demonstration', description: 'Demonstration farm where ducks control weeds and pests in rice paddies, with training for farmer groups.', category: 'Agriculture & Fisheries', municipality: 'Clarin', barangays: ['Bonbon'], start: '2026-11-16', end: '2027-02-26', budget: 120000, beneficiaries: 40, sectors: ['Farmers'], status: 'for_president',
        events: trail('Clarin', 'for_president', ['2026-10-08T10:00', '2026-10-16T09:00']) }),
      rec('Candijay', 'CAN-2026-028', { title: 'Mat Weaving Enterprise for Women', description: 'Training on banig weaving, natural dyes and pricing, with a link to the provincial pasalubong market.', category: 'Livelihood', municipality: 'Candijay', barangays: ['Cogtong'], start: '2026-11-23', end: '2026-12-18', budget: 75000, beneficiaries: 30, sectors: ['Women', 'MSME owners'], status: 'for_president',
        events: trail('Candijay', 'for_president', ['2026-10-19T08:30', '2026-10-26T14:00']) }),
      rec('Main', 'MAIN-2026-033', { title: 'Mental Health First Aid for Barangay Health Workers', description: 'Two-day training for barangay health workers on recognising distress, first conversations and referral.', category: 'Health', municipality: 'Tagbilaran', barangays: ['Dampas'], start: '2026-12-02', end: '2026-12-04', budget: 90000, beneficiaries: 40, sectors: ['Health workers'], status: 'for_president',
        events: trail('Main', 'for_president', ['2026-10-20T09:00', '2026-10-27T16:00']) }),
      rec('Main', 'MAIN-2026-018', { title: 'Community Nutrition and Feeding Program', description: 'Supplementary feeding and nutrition classes for families of undernourished children.', category: 'Health', municipality: 'Tagbilaran', barangays: ['Booy'], start: '2026-06-10', end: '2026-12-05', budget: 150000, beneficiaries: 80, sectors: ['Women', 'Youth'], status: 'ongoing',
        events: trail('Main', 'ongoing', ['2026-05-02T09:00', '2026-05-08T10:00', '2026-05-22T11:00', '2026-06-10T08:00']) }),
      rec('Calape', 'CAL-2026-019', { title: 'Seaweed Farming Livelihood Training for Coastal Women', description: 'Hands-on training on seaweed farming, drying and selling for women in coastal households.', category: 'Agriculture & Fisheries', municipality: 'Calape', barangays: ['Sampoloc'], start: '2026-02-02', end: '2026-04-24', budget: 85000, beneficiaries: 40, sectors: ['Women', 'Fisherfolk'], status: 'completed',
        events: trail('Calape', 'completed', ['2025-12-01T09:00', '2025-12-05T10:00', '2025-12-18T11:00', '2026-02-02T08:00', '2026-05-02T15:00']),
        report: { actualStart: '2026-02-02', actualEnd: '2026-04-24', actualBudget: 81000, actualBeneficiaries: 38, narrative: '38 women started seaweed plots; the first harvest sold to a Tagbilaran buyer.', photos: [samplePhoto('Seaweed planting lines', 170), samplePhoto('Drying the first harvest', 50)] } }),
      rec('Calape', 'CAL-2026-027', { title: 'Coconut Sap Sugar Processing', description: 'Training on collecting coconut sap and processing it into sugar for sale.', category: 'Livelihood', municipality: 'Calape', barangays: ['Lucob'], start: '2026-12-01', end: '2026-12-12', budget: 65000, beneficiaries: 25, sectors: ['Farmers', 'MSME owners'], status: 'approved',
        events: trail('Calape', 'approved', ['2026-09-15T09:00', '2026-09-22T10:00', '2026-10-06T11:00']) }),
      rec('Candijay', 'CAN-2026-011', { title: 'Coastal Cleanup and Waste Segregation', description: 'Monthly coastal cleanups and household waste segregation training with the barangay.', category: 'Environment', municipality: 'Candijay', barangays: ['Cogtong'], start: '2026-06-01', end: '2026-08-30', budget: 45000, beneficiaries: 150, sectors: ['Youth', 'Fisherfolk'], status: 'ongoing',
        events: trail('Candijay', 'ongoing', ['2026-04-20T09:00', '2026-04-24T10:00', '2026-05-08T11:00', '2026-06-01T08:00']) }),
      rec('Candijay', 'CAN-2026-030', { title: 'Fishing Gear Distribution', description: 'Distribution of nets and floats to fisherfolk associations.', category: 'Livelihood', municipality: 'Candijay', barangays: ['Cogtong'], start: '2026-11-05', end: '2026-11-05', budget: 110000, beneficiaries: 60, sectors: ['Fisherfolk'], status: 'declined',
        events: trail('Candijay', 'for_president', ['2026-09-28T09:00', '2026-10-02T10:00'], { to: 'declined', by: 'pres', at: '2026-10-09T11:00', remark: 'Distributing equipment alone is outside extension services. Resubmit it with a training component.' }) }),
      rec('Clarin', 'CLA-2026-008', { title: 'Rice Seed Selection and Pest Management Workshop', description: 'Workshop on selecting quality seed and managing rice pests without heavy pesticide use.', category: 'Agriculture & Fisheries', municipality: 'Clarin', barangays: ['Bacani'], start: '2026-04-06', end: '2026-05-22', budget: 55000, beneficiaries: 40, sectors: ['Farmers'], status: 'completed',
        events: trail('Clarin', 'completed', ['2026-02-10T09:00', '2026-02-16T10:00', '2026-03-02T11:00', '2026-04-06T08:00', '2026-05-28T15:00']),
        report: { actualStart: '2026-04-06', actualEnd: '2026-05-22', actualBudget: 52000, actualBeneficiaries: 44, narrative: '44 farmers adopted seed selection; two groups now share a seed bank.', photos: [samplePhoto('Seed selection practice', 60)] } }),
      rec('Bilar', 'BIL-2026-015', { title: 'Dental Health and Hygiene Caravan', description: 'Dental check-ups, fluoride application and toothbrushing lessons for children.', category: 'Health', municipality: 'Bilar', barangays: ['Riverside'], start: '2026-06-03', end: '2026-06-24', budget: 35000, beneficiaries: 100, sectors: ['Youth'], status: 'completed',
        events: trail('Bilar', 'completed', ['2026-04-02T09:00', '2026-04-08T10:00', '2026-04-22T11:00', '2026-06-03T08:00', '2026-06-30T15:00']),
        report: { actualStart: '2026-06-03', actualEnd: '2026-06-24', actualBudget: 33500, actualBeneficiaries: 120, narrative: '120 children checked; 31 referred to the rural health unit for treatment.', photos: [samplePhoto('Toothbrushing lesson', 330), samplePhoto('Dental check-ups', 210)] } }),
    ];
  }

  function seed() {
    const db = {
      year: YEAR,
      allocations: { ...ALLOCATIONS },
      nextNo: 25,
      requests: [
        {
          id: 'r14', code: 'BAL-2026-014', requestedBy: 'ed', encodedBy: 'asst',
          title: 'Tilapia Pond Culture Livelihood Training for Farm Families',
          description: 'Hands-on training on tilapia pond construction, feeding and selling for farm households, to add a second income source.',
          category: 'Agriculture & Fisheries', municipality: 'Balilihan', barangays: ['Cabad'],
          start: '2026-09-01', end: '2026-11-15', budget: 85000, beneficiaries: 40, sectors: ['Farmers', 'Women'], partners: 'BFAR Region 7, Cabad Barangay Council', letter: 'Cabad_letter_of_request.pdf',
          status: 'ongoing',
          overlap: { score: 87, match: OTHER_CAMPUSES[0], terms: ['tilapia', 'pond', 'income'], resolution: 'coordinate', resolvedBy: 'cd' },
          events: [
            ev('2026-07-10T09:12', 'asst', null, 'draft'),
            ev('2026-07-14T10:30', 'ed', 'draft', 'submitted'),
            ev('2026-07-18T14:05', 'cd', 'submitted', 'for_president', 'Coordinate with Main Campus on the training schedule.'),
            ev('2026-07-29T11:20', 'pres', 'for_president', 'approved'),
            ev('2026-09-01T08:00', 'ed', 'approved', 'ongoing'),
          ],
          remarks: [{ at: '2026-07-18T14:05', by: 'cd', body: 'Main Campus has an aquaculture seminar in the same barangay. Please align dates with their extension office.' }],
          report: null,
        },
        {
          id: 'r19', code: 'BAL-2026-019', requestedBy: 'ed', encodedBy: 'ed',
          title: 'Watershed Rehabilitation and Tree Planting',
          description: 'Plant native trees along eroded riverbanks and train youth volunteers to monitor the seedlings.',
          category: 'Environment', municipality: 'Balilihan', barangays: ['Magsija'],
          start: '2026-11-20', end: '2026-12-12', budget: 60000, beneficiaries: 120, sectors: ['Youth', 'Farmers'], partners: 'DENR CENRO Tagbilaran', letter: '',
          status: 'for_president',
          overlap: { score: 74, match: OTHER_CAMPUSES[5], terms: ['tree', 'planting', 'watershed'], resolution: 'allow', resolvedBy: 'cd' },
          events: [ev('2026-10-12T15:40', 'ed', null, 'submitted'), ev('2026-10-20T09:15', 'cd', 'submitted', 'for_president', 'Different sites within Magsija. Allowed both.')],
          remarks: [], report: null,
        },
        {
          id: 'r21', code: 'BAL-2026-021', requestedBy: 'ed', encodedBy: 'asst',
          title: 'Basic Bookkeeping for Sari-sari Store Owners',
          description: 'Two-day workshop on simple bookkeeping, inventory and pricing for small store owners.',
          category: 'Livelihood', municipality: 'Balilihan', barangays: ['Dorol'],
          start: '2026-11-24', end: '2026-11-25', budget: 35000, beneficiaries: 30, sectors: ['MSME owners', 'Women'], partners: 'DTI Bohol', letter: 'Dorol_request.pdf',
          status: 'submitted', overlap: null,
          events: [ev('2026-10-22T13:00', 'asst', null, 'draft'), ev('2026-10-23T08:45', 'ed', 'draft', 'submitted')],
          remarks: [], report: null,
        },
        {
          id: 'r22', code: 'BAL-2026-022', requestedBy: 'ed', encodedBy: 'asst',
          title: 'Digital Literacy for Barangay Health Workers',
          description: 'Train barangay health workers to use smartphones and spreadsheets for keeping health records and reporting to the RHU.',
          category: 'Technology & Digital', municipality: 'Balilihan', barangays: ['Boctol', 'Cantomimbo'],
          start: '2026-12-01', end: '2026-12-04', budget: 18000, beneficiaries: 25, sectors: ['Health workers'], partners: 'Balilihan Rural Health Unit', letter: '',
          status: 'submitted', overlap: null,
          events: [ev('2026-10-26T15:00', 'asst', null, 'draft'), ev('2026-10-27T10:10', 'ed', 'draft', 'submitted')],
          remarks: [{ at: '2026-10-28T09:00', by: 'cd', body: 'Please confirm the RHU can release the health workers on these dates.' }], report: null,
        },
        {
          id: 'r23', code: 'BAL-2026-023', requestedBy: 'ed', encodedBy: 'ed',
          title: 'Integrated Organic Farming and Vermicomposting for 4Ps Households',
          description: 'Five-week program on organic vegetable gardening and vermicomposting for 4Ps households, with starter kits and seedlings.',
          category: 'Agriculture & Fisheries', municipality: 'Balilihan', barangays: ['Datag Norte', 'Datag Sur', 'Haguilanan Grande'],
          start: '2026-11-16', end: '2026-12-18', budget: 250000, beneficiaries: 90, sectors: ['4Ps households', 'Farmers'], partners: 'Municipal Agriculture Office, DSWD', letter: 'MAO_endorsement.pdf',
          status: 'submitted',
          overlap: { score: 72, match: OTHER_CAMPUSES[2], terms: ['organic', 'vegetable', 'compost'], resolution: null },
          events: [ev('2026-10-28T08:20', 'ed', null, 'submitted')],
          remarks: [], report: null,
        },
        {
          id: 'r17', code: 'BAL-2026-017', requestedBy: 'ed', encodedBy: 'asst',
          title: 'First Aid and Disaster Preparedness Training',
          description: 'Basic first aid, evacuation drills and family preparedness planning for barangay responders.',
          category: 'Health', municipality: 'Balilihan', barangays: ['San Roque'],
          start: '2026-11-16', end: '2026-11-18', budget: 40000, beneficiaries: 50, sectors: ['Youth', 'Women'], partners: 'MDRRMO Balilihan', letter: '',
          status: 'returned', overlap: null,
          events: [ev('2026-10-05T09:00', 'ed', null, 'submitted'), ev('2026-10-09T16:20', 'cd', 'submitted', 'for_president'), ev('2026-10-27T11:00', 'pres', 'for_president', 'returned', "Attach the barangay's letter of request and add a budget breakdown.")],
          remarks: [], report: null,
        },
        {
          id: 'r12', code: 'BAL-2026-012', requestedBy: 'ed', encodedBy: 'ed',
          title: 'Reading Remediation for Grade 3 Learners',
          description: 'Saturday reading sessions for grade 3 learners who are below reading level, with take-home materials for parents.',
          category: 'Education', municipality: 'Balilihan', barangays: ['Hanopol Norte'],
          start: '2026-11-07', end: '2027-02-27', budget: 30000, beneficiaries: 35, sectors: ['Youth', 'Teachers'], partners: 'DepEd Balilihan District', letter: '',
          status: 'approved', overlap: null,
          events: [ev('2026-09-02T10:00', 'ed', null, 'submitted'), ev('2026-09-04T09:30', 'cd', 'submitted', 'for_president'), ev('2026-09-15T14:00', 'pres', 'for_president', 'approved')],
          remarks: [], report: null,
        },
        {
          id: 'r15', code: 'BAL-2026-015', requestedBy: 'ed', encodedBy: 'ed',
          title: 'Senior Citizens Wellness and Nutrition Program',
          description: 'Monthly wellness checks, light exercise and nutrition talks for senior citizens.',
          category: 'Health', municipality: 'Balilihan', barangays: ['Sal-ing'],
          start: '2026-08-01', end: '2026-10-05', budget: 45000, beneficiaries: 60, sectors: ['Senior citizens'], partners: 'Office of Senior Citizens Affairs', letter: '',
          status: 'ongoing', overlap: null,
          events: [ev('2026-06-20T10:00', 'ed', null, 'submitted'), ev('2026-06-24T15:00', 'cd', 'submitted', 'for_president'), ev('2026-07-03T09:00', 'pres', 'for_president', 'approved'), ev('2026-08-01T08:00', 'ed', 'approved', 'ongoing')],
          remarks: [], report: null,
        },
        {
          id: 'r24', code: 'BAL-2026-024', requestedBy: 'ed', encodedBy: 'asst',
          title: 'Water Sanitation and Hygiene Seminar',
          description: 'Seminar on safe water storage, handwashing and toilet use for households.',
          category: 'Health', municipality: 'Balilihan', barangays: ['Sagasa'],
          start: '2026-12-08', end: '2026-12-08', budget: 15000, beneficiaries: 45, sectors: ['Women'], partners: '', letter: '',
          status: 'draft', overlap: null,
          events: [ev('2026-10-26T16:30', 'asst', null, 'draft')], remarks: [], report: null,
        },
        {
          id: 'r11', code: 'BAL-2026-011', requestedBy: 'ed', encodedBy: 'asst',
          title: 'Coco Fiber Weaving and Handicraft Enterprise Training',
          description: 'Training on coco coir weaving and product finishing, with a session on selling at trade fairs.',
          category: 'Livelihood', municipality: 'Balilihan', barangays: ['Baucan Norte'],
          start: '2026-01-20', end: '2026-03-28', budget: 70000, beneficiaries: 32, sectors: ['Women', 'MSME owners'], partners: 'DTI Bohol', letter: '',
          status: 'completed', overlap: null,
          events: [ev('2025-12-01T09:00', 'ed', null, 'submitted'), ev('2025-12-05T10:00', 'cd', 'submitted', 'for_president'), ev('2025-12-15T10:00', 'pres', 'for_president', 'approved'), ev('2026-01-20T08:00', 'ed', 'approved', 'ongoing'), ev('2026-04-02T15:00', 'ed', 'ongoing', 'completed')],
          remarks: [], report: { actualStart: '2026-01-20', actualEnd: '2026-03-28', actualBudget: 64000, actualBeneficiaries: 34, narrative: '34 women completed the training. 12 now supply the Balilihan pasalubong center.', photos: [samplePhoto('Coco fiber weaving session', 260), samplePhoto('Finished mats and bags', 30), samplePhoto('Attendance sheet, day 1', 200)] },
        },
        {
          id: 'r08', code: 'BAL-2026-008', requestedBy: 'ed', encodedBy: 'asst',
          title: 'Meat Processing: Longganisa and Tocino',
          description: 'Training on safe meat processing for market, with packaging and labeling.',
          category: 'Livelihood', municipality: 'Balilihan', barangays: ['Cabad'],
          start: '2026-02-10', end: '2026-04-15', budget: 55000, beneficiaries: 28, sectors: ['Women', 'Farmers'], partners: 'DOST Bohol', letter: '',
          status: 'completed', overlap: null,
          events: [ev('2026-01-08T09:00', 'ed', null, 'submitted'), ev('2026-01-09T10:00', 'cd', 'submitted', 'for_president'), ev('2026-01-20T10:00', 'pres', 'for_president', 'approved'), ev('2026-02-10T08:00', 'ed', 'approved', 'ongoing'), ev('2026-04-20T15:00', 'ed', 'ongoing', 'completed')],
          remarks: [], report: { actualStart: '2026-02-10', actualEnd: '2026-04-15', actualBudget: 52000, actualBeneficiaries: 28, narrative: 'Participants formed a processors association and registered with the municipality.', photos: [samplePhoto('Longganisa preparation', 10), samplePhoto('Packaging and labeling', 140)] },
        },
        {
          id: 'r31', code: 'BAL-2025-031', requestedBy: 'ed', encodedBy: 'ed',
          title: 'Integrated Pest Management Orientation for Rice Farmers',
          description: 'Orientation on pest monitoring and safer pesticide use for rice farmers.',
          category: 'Agriculture & Fisheries', municipality: 'Balilihan', barangays: ['Del Carmen Sur'],
          start: '2025-03-04', end: '2025-05-17', budget: 38000, beneficiaries: 55, sectors: ['Farmers'], partners: 'DA-ATI Region 7', letter: '',
          status: 'archived', overlap: null,
          events: [ev('2025-02-01T09:00', 'ed', null, 'submitted'), ev('2025-02-04T10:00', 'cd', 'submitted', 'for_president'), ev('2025-02-12T10:00', 'pres', 'for_president', 'approved'), ev('2025-03-04T08:00', 'ed', 'approved', 'ongoing'), ev('2025-05-20T15:00', 'ed', 'ongoing', 'completed'), ev('2025-12-31T23:59', 'ed', 'completed', 'archived')],
          remarks: [], report: { actualStart: '2025-03-04', actualEnd: '2025-05-17', actualBudget: 36500, actualBeneficiaries: 61, narrative: 'Farmers set up a pest monitoring schedule with the municipal agriculturist.', photos: [samplePhoto('Field walk with rice farmers', 100), samplePhoto('Group photo with DA-ATI', 220)] },
        },
      ],
      notifications: [
        { to: 'ed', id: 'n1', at: '2026-10-28T09:00', kind: 'info', read: false, text: 'Dr. Ramon Tabada left a remark on Digital Literacy for Barangay Health Workers.', ref: 'r22' },
        { to: 'ed', id: 'n2', at: '2026-10-28T07:00', kind: 'overdue', read: false, text: 'Report overdue: Senior Citizens Wellness and Nutrition Program ended on Oct 5.', ref: 'r15' },
        { to: 'ed', id: 'n3', at: '2026-10-27T11:00', kind: 'returned', read: false, text: 'The University President returned First Aid and Disaster Preparedness Training.', ref: 'r17' },
        { to: 'ed', id: 'n4', at: '2026-10-28T08:21', kind: 'overdue', read: true, text: 'Possible overlap flagged on Integrated Organic Farming and Vermicomposting for 4Ps Households. The Campus Director will decide when reviewing it.', ref: 'r23' },
        { to: 'ed', id: 'n5', at: '2026-10-20T09:15', kind: 'approved', read: true, text: 'The Campus Director approved Watershed Rehabilitation and Tree Planting. It is now with the President.', ref: 'r19' },
        { to: 'cd', id: 'c1', at: '2026-10-28T08:20', kind: 'request', read: false, text: 'Ma. Elena Racines submitted Integrated Organic Farming and Vermicomposting for 4Ps Households for your approval. EXTRACK flagged a possible overlap.', ref: 'r23' },
        { to: 'cd', id: 'c2', at: '2026-10-27T10:10', kind: 'request', read: false, text: 'Ma. Elena Racines submitted Digital Literacy for Barangay Health Workers for your approval.', ref: 'r22' },
        { to: 'cd', id: 'c3', at: '2026-10-27T11:00', kind: 'returned', read: true, text: 'The University President returned First Aid and Disaster Preparedness Training to the Extension Director.', ref: 'r17' },
        { to: 'pres', id: 'p1', at: '2026-10-27T16:00', kind: 'request', read: false, text: 'The Main Campus Director approved Mental Health First Aid for Barangay Health Workers. It needs your final approval.', ref: 'main-2026-033' },
        { to: 'pres', id: 'p2', at: '2026-10-26T14:00', kind: 'request', read: false, text: 'The Candijay Campus Director approved Mat Weaving Enterprise for Women. It needs your final approval.', ref: 'can-2026-028' },
        { to: 'pres', id: 'p3', at: '2026-10-21T15:30', kind: 'request', read: false, text: 'The Calape Campus Director approved Tree Planting along the Abatan River Watershed. EXTRACK flagged a possible overlap with Balilihan Campus.', ref: 'cal-2026-035' },
        { to: 'pres', id: 'p4', at: '2026-10-20T09:15', kind: 'request', read: true, text: 'The Balilihan Campus Director approved Watershed Rehabilitation and Tree Planting. It needs your final approval.', ref: 'r19' },
        { to: 'pres', id: 'p5', at: '2026-10-16T09:00', kind: 'request', read: true, text: 'The Clarin Campus Director approved Rice-Duck Farming Demonstration. It needs your final approval.', ref: 'cla-2026-021' },
        { to: 'pres', id: 'p6', at: '2026-10-14T07:00', kind: 'overdue', read: true, text: 'Candijay Campus: Coastal Cleanup and Waste Segregation ended on Aug 30 with no accomplishment report.', ref: 'can-2026-011' },
        { to: 'cd', id: 'c4', at: '2026-10-23T08:45', kind: 'request', read: true, text: 'Ma. Elena Racines submitted Basic Bookkeeping for Sari-sari Store Owners for your approval.', ref: 'r21' },
      ],
    };
    db.requests.push(...otherCampusRequests());
    db.requests.forEach(r => {
      const docs = r.letter ? [sampleDoc('letter', r.letter)] : [];
      (SAMPLE_DOCS[r.id] || []).forEach(([kind, name]) => docs.push(sampleDoc(kind, name)));
      docs.forEach(d => { d.by = r.requestedBy; d.at = r.events[0].at; });
      r.attachments = docs;
      delete r.letter;
    });
    return db;
  }

  // ---------- helpers ----------
  const peso = n => '₱' + Math.round(n || 0).toLocaleString('en-PH');
  const parse = d => new Date(d.length === 10 ? d + 'T00:00' : d);
  const daysBetween = (a, b) => Math.round((parse(b) - parse(a)) / 86400000);
  const fmtDate = d => d ? parse(d).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : '';
  const fmtShort = d => parse(d).toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
  const fmtDateTime = d => parse(d).toLocaleString('en-US', { month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit' });
  const nowIso = () => { const d = new Date(); d.setMinutes(d.getMinutes() - d.getTimezoneOffset()); return d.toISOString().slice(0, 16); };

  function tokens(text) {
    return (text || '').toLowerCase().replace(/[^a-z\s-]/g, ' ').split(/\s+/)
      .map(w => w.replace(/^-+|-+$/g, ''))
      .filter(w => w.length > 2 && !STOP.has(w))
      .map(w => (w.length > 4 && w.endsWith('s') && !w.endsWith('ss')) ? w.slice(0, -1) : w);
  }
  function themeOf(w) { const i = THEMES.findIndex(g => g.includes(w) || g.includes(w + 's')); return i < 0 ? null : 't' + i; }

  function textSimilarity(a, b) {
    const ta = new Set(tokens(a)), tb = new Set(tokens(b));
    const shared = new Set();
    let hits = 0;
    ta.forEach(w => { if (tb.has(w)) { hits++; shared.add(w); } });
    // theme matches count as partial hits, so different wording on the same theme still scores
    const themesB = new Map();
    tb.forEach(w => { const t = themeOf(w); if (t) themesB.set(t, w); });
    ta.forEach(w => { const t = themeOf(w); if (t && !tb.has(w) && themesB.has(t)) { hits += 0.7; shared.add(w); } });
    const denom = Math.min(ta.size, tb.size) || 1;
    return { score: Math.min(1, hits / Math.max(3, denom * 0.6)), shared: [...shared].slice(0, 5) };
  }

  function overlapScore(req, other) {
    const text = textSimilarity(req.title + ' ' + req.description, other.title + ' ' + other.description);
    const sameBrgy = req.municipality === other.municipality && req.barangays.some(b => other.barangays.includes(b));
    const place = sameBrgy ? 1 : (req.municipality === other.municipality ? 0.45 : 0);
    let time = 0;
    if (req.start && req.end) {
      const overlapDays = Math.min(parse(req.end), parse(other.end)) - Math.max(parse(req.start), parse(other.start));
      if (overlapDays >= 0) time = 1;
      else {
        const gap = Math.abs(overlapDays) / 86400000;
        time = gap <= 60 ? 0.6 : gap <= 180 ? 0.3 : 0;
      }
    }
    const score = Math.round((text.score * 0.5 + place * 0.3 + time * 0.2) * 100);
    return { score, terms: text.shared, sameBrgy, overlapDays: time === 1 };
  }

  function suggestCategory(text) {
    const t = (text || '').toLowerCase();
    let best = null, bestHits = 0;
    Object.entries(CATEGORY_HINTS).forEach(([cat, words]) => {
      const hits = words.filter(w => t.includes(w)).length;
      if (hits > bestHits) { best = cat; bestHits = hits; }
    });
    return best;
  }

  function emptyForm() {
    return { id: null, title: '', description: '', category: '', municipality: 'Balilihan', barangays: [], start: '', end: '', budget: '', beneficiaries: '', sectors: [], partners: '', attachments: [] };
  }

  // ---------- Alpine components ----------
  // Both dashboards share one data store (same localStorage key), so a request the Extension Director
  // submits shows up for the Campus Director, and the Campus Director's decision shows up back.

  // copies getters as getters, which object spread would not
  function compose(...parts) {
    const o = {};
    parts.forEach(p => Object.defineProperties(o, Object.getOwnPropertyDescriptors(p)));
    return o;
  }

  function shared() {
    const mapState = { map: null, layer: null };
    return {
      PEOPLE, CATEGORIES, SECTORS, PLACES, STATUS, TODAY, CAMPUSES,
      db: null,
      view: 'dashboard',
      filter: { tab: 'all', q: '', status: '', category: '', campus: '' },
      selectedId: null,
      modal: null,
      notifOpen: false,
      toast: null,
      lightbox: null,
      theme: 'light',
      mapFilter: { campus: '', category: '', ongoing: true, completed: true },
      mapSel: null,

      init() {
        let saved = null;
        try { saved = JSON.parse(localStorage.getItem(STORE_KEY)); } catch (e) { /* storage unavailable */ }
        this.db = saved && saved.requests ? saved : seed();
        try { this.theme = localStorage.getItem('extrack.theme.v2') === 'dark' ? 'dark' : 'light'; } catch (e) { }
        this.applyTheme();
        this.$watch('db', () => { this.persist(); this.drawMarkers(); }, { deep: true });
        this.$watch('mapFilter', () => this.drawMarkers(), { deep: true });
        this.$watch('mapSel', () => this.drawMarkers(), { deep: true });
        // pick up changes made in the other dashboard (another tab)
        window.addEventListener('storage', e => {
          if (e.key !== STORE_KEY || !e.newValue) return;
          try { this.db = JSON.parse(e.newValue); } catch (err) { }
        });
        window.addEventListener('keydown', e => {
          if (this.lightbox && (e.key === 'ArrowRight' || e.key === 'ArrowLeft')) return this.stepPhoto(e.key === 'ArrowRight' ? 1 : -1);
          if (e.key !== 'Escape') return;
          if (this.lightbox) this.lightbox = null; else if (this.modal) this.modal = null; else if (this.selectedId) this.selectedId = null; else this.notifOpen = false;
        });
      },
      persist() {
        try { localStorage.setItem(STORE_KEY, JSON.stringify(this.db)); }
        catch (e) { if (e && e.name === 'QuotaExceededError') this.notify('Browser storage is full, so the latest photos were not saved. Remove some photos or reset the demo data.', 'bad'); }
      },
      resetDemo() {
        this.db = seed(); this.selectedId = null; this.modal = null; this.view = 'dashboard';
        this.notify('Demo data reset');
      },
      applyTheme() {
        const root = document.documentElement;
        if (this.theme === 'dark') root.setAttribute('data-theme', 'dark'); else root.removeAttribute('data-theme');
      },
      cycleTheme() {
        this.theme = this.theme === 'dark' ? 'light' : 'dark';
        try { localStorage.setItem('extrack.theme.v2', this.theme); } catch (e) { }
        this.applyTheme();
      },

      // formatting exposed to templates
      peso, fmtDate, fmtDateTime, fmtShort,
      person(id) { return PEOPLE[id] || { name: id, title: '' }; },
      get me() { return PEOPLE[this.actor]; },
      greeting() { const h = new Date().getHours(); return h < 12 ? 'Good morning' : h < 18 ? 'Good afternoon' : 'Good evening'; },
      todayLabel() { return fmtDate(TODAY); },

      // ---------- derived lists ----------
      // directors see only their own campus; the President (campus null) sees every campus
      get requests() { return this.campus ? this.db.requests.filter(r => campusOf(r) === this.campus) : this.db.requests; },
      campusName(r) { return campusOf(r) + ' Campus'; },
      waitingSince(r) {
        const e = this.lastTo(r, r.status);
        return e ? daysBetween(e.at.slice(0, 10), TODAY) : 0;
      },
      byId(id) { return this.db.requests.find(r => r.id === id); },
      get selected() { return this.selectedId ? this.byId(this.selectedId) : null; },
      get waitingApproval() { return this.requests.filter(r => ['submitted', 'for_president'].includes(r.status)); },
      get returnedToMe() { return this.requests.filter(r => r.status === 'returned'); },
      get reportsDue() { return this.requests.filter(r => r.status === 'ongoing' && r.end < TODAY); },
      get overlapOpen() { return this.requests.filter(r => r.overlap && !r.overlap.resolution && !['declined', 'completed', 'archived'].includes(r.status)); },
      get ongoing() {
        return this.requests.filter(r => r.status === 'ongoing' || r.status === 'approved')
          .sort((a, b) => a.end.localeCompare(b.end));
      },
      get archive() { return this.requests.filter(r => ['completed', 'archived'].includes(r.status)).sort((a, b) => b.end.localeCompare(a.end)); },
      lastEvent(r) { return r.events[r.events.length - 1]; },
      lastTo(r, to) { return [...r.events].reverse().find(e => e.to === to); },

      endsIn(r) {
        const d = daysBetween(TODAY, r.end);
        if (r.status === 'approved') {
          const s = daysBetween(TODAY, r.start);
          return s < 0 ? { text: 'Start overdue by ' + Math.abs(s) + ' days', tone: 'bad' } : { text: 'Starts in ' + s + ' days', tone: 'muted' };
        }
        if (d < 0) return { text: 'Report overdue, ended ' + Math.abs(d) + ' days ago', tone: 'bad' };
        if (d <= 7) return { text: 'Ends in ' + d + ' days', tone: 'warn' };
        return { text: 'Ends in ' + d + ' days', tone: 'muted' };
      },

      // ---------- requests list (tabs come from each role) ----------
      get filtered() {
        const f = this.filter, q = f.q.trim().toLowerCase();
        let list = this.inTab(f.tab);
        if (f.status) list = list.filter(r => r.status === f.status);
        if (f.category) list = list.filter(r => r.category === f.category);
        if (f.campus) list = list.filter(r => campusOf(r) === f.campus);
        if (q) list = list.filter(r => (r.title + ' ' + r.code + ' ' + r.barangays.join(' ') + ' ' + r.municipality).toLowerCase().includes(q));
        return [...list].sort((a, b) => this.lastEvent(b).at.localeCompare(this.lastEvent(a).at));
      },
      tabCount(tab) { return this.inTab(tab).length; },
      openList(tab, campus) { this.filter = { tab, q: '', status: '', category: '', campus: campus || '' }; this.view = 'requests'; this.selectedId = null; },

      // ---------- budget ----------
      get budget() { return this.budgetOf(this.campus); },
      budgetOf(campus) {
        const inYear = r => r.start.startsWith(String(this.db.year));
        const sum = (arr, f) => arr.reduce((t, r) => t + f(r), 0);
        const rs = this.db.requests.filter(r => campusOf(r) === campus && inYear(r));
        const allocation = this.db.allocations[campus] || 0;
        const spent = sum(rs.filter(r => ['completed', 'archived'].includes(r.status)), r => r.report ? r.report.actualBudget : r.budget);
        const committed = sum(rs.filter(r => ['approved', 'ongoing'].includes(r.status)), r => r.budget);
        const pending = sum(rs.filter(r => ['submitted', 'for_president'].includes(r.status)), r => r.budget);
        const remaining = allocation - committed - spent;
        const pct = v => allocation ? Math.max(0, Math.min(100, v / allocation * 100)) : 0;
        return { allocation, spent, committed, pending, remaining, pct };
      },
      budgetFor(req) {
        // budget is charged to the year of the start date
        const year = (req.start || TODAY).slice(0, 4);
        const sameYear = String(this.db.year) === year;
        if (!sameYear) return { year, sameYear, over: false, remaining: null };
        const b = this.budgetOf(campusOf(req));
        const counted = ['approved', 'ongoing', 'completed', 'archived'].includes(req.status);
        const remaining = counted ? b.remaining + Number(req.budget || 0) : b.remaining;
        const amount = Number(req.budget || 0);
        return { year, sameYear, remaining, over: amount > remaining, by: amount - remaining, after: remaining - amount };
      },

      // ---------- barangay coverage ----------
      get coverage() {
        const counts = {};
        this.requests.filter(r => ['approved', 'ongoing', 'completed', 'archived'].includes(r.status))
          .forEach(r => r.barangays.forEach(b => { const k = r.municipality === 'Balilihan' ? b : b + ', ' + r.municipality; counts[k] = (counts[k] || 0) + 1; }));
        const rows = Object.entries(counts).map(([name, n]) => ({ name, n })).sort((a, b) => b.n - a.n || a.name.localeCompare(b.name));
        const reached = new Set(Object.keys(counts).filter(k => !k.includes(',')));
        return { rows, reached: reached.size, total: PLACES.Balilihan.length, notReached: PLACES.Balilihan.filter(b => !reached.has(b)) };
      },

      // ---------- status ----------
      steps(r) {
        const labels = ['Submitted', 'Campus approval', 'President approval', 'Ongoing', 'Completed'];
        let at = { draft: 0, submitted: 1, for_president: 2, approved: 3, ongoing: 3, completed: 5, archived: 5 }[r.status];
        let flag = null;
        if (r.status === 'returned' || r.status === 'declined') {
          // show the step where it was stopped
          const stop = this.lastTo(r, r.status);
          at = stop && stop.from === 'for_president' ? 2 : 1;
          flag = r.status === 'returned' ? 'warn' : 'bad';
        }
        return labels.map((label, i) => ({ label, state: i < at ? 'done' : i === at ? (flag || 'now') : 'todo' }));
      },
      statusTone(r) { return STATUS[r.status].tone; },

      // ---------- actions ----------
      open(id) { this.selectedId = id; this.notifOpen = false; },
      notify(msg, tone) { this.toast = { msg, tone: tone || 'ok', id: Math.random() }; const id = this.toast.id; setTimeout(() => { if (this.toast && this.toast.id === id) this.toast = null; }, 3200); },
      // to: 'ed', 'cd' or both
      pushNotification(text, ref, kind, to) {
        [].concat(to || this.audience).forEach((who, i) => {
          this.db.notifications.unshift({ id: 'n' + Date.now() + i, to: who, at: nowIso(), kind: kind || 'info', read: false, text, ref });
        });
      },
      move(r, to, remark, by) {
        r.events.push(ev(nowIso(), by || this.actor, r.status, to, remark));
        r.status = to;
      },
      addRemark(r, input) {
        const body = (input.value || '').trim(); if (!body) return;
        r.remarks.push({ at: nowIso(), by: this.actor, body }); input.value = '';
      },
      // ---------- extension map: every campus, so directors can learn from each other ----------
      CAMPUS_COLORS,
      get mapServices() {
        const f = this.mapFilter;
        return this.db.requests.filter(r =>
          ((f.ongoing && r.status === 'ongoing') || (f.completed && ['completed', 'archived'].includes(r.status)))
          && (!f.campus || campusOf(r) === f.campus) && (!f.category || r.category === f.category));
      },
      get mapPlaces() {
        const places = {};
        this.mapServices.forEach(r => r.barangays.forEach(b => {
          const key = r.municipality + '/' + b;
          if (!places[key]) places[key] = { key, name: b + ', ' + r.municipality, latlng: placeOf(r.municipality, b), services: [] };
          places[key].services.push(r);
        }));
        return Object.values(places).map(p => ({ ...p, services: p.services.sort((a, b) => b.start.localeCompare(a.start)) }));
      },
      get mapPlace() { return this.mapSel ? this.mapPlaces.find(p => p.key === this.mapSel.key) || null : null; },
      get mapService() { const p = this.mapPlace; return p ? p.services[Math.min(this.mapSel.i, p.services.length - 1)] : null; },
      selectPlace(key) { this.mapSel = { key, i: 0 }; },
      campusColor(c) { return CAMPUS_COLORS[c] || '#47445F'; },
      isOwn(r) { return !this.campus || campusOf(r) === this.campus; },
      initMap(el) {
        if (!window.L || !el) return;
        if (mapState.map) { mapState.map.invalidateSize(); return; }
        mapState.map = L.map(el, { scrollWheelZoom: true }).setView([9.82, 124.15], 10);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 18, attribution: '&copy; OpenStreetMap contributors' }).addTo(mapState.map);
        mapState.layer = L.layerGroup().addTo(mapState.map);
        this.drawMarkers();
      },
      drawMarkers() {
        if (!mapState.layer) return;
        mapState.layer.clearLayers();
        this.mapPlaces.forEach(p => {
          const campuses = [...new Set(p.services.map(campusOf))];
          const color = campuses.length === 1 ? this.campusColor(campuses[0]) : '#47445F';
          const n = p.services.length;
          const on = this.mapSel && this.mapSel.key === p.key;
          const icon = L.divIcon({ className: on ? 'pin on' : 'pin', html: '<span style="background:' + color + '">' + n + '</span>', iconSize: [30, 30], iconAnchor: [15, 15] });
          L.marker(p.latlng, { icon, zIndexOffset: on ? 1000 : 0, title: p.name + ': ' + n + (n === 1 ? ' service' : ' services'), keyboard: true })
            .on('click', () => this.selectPlace(p.key)).addTo(mapState.layer);
        });
      },

      // ---------- supporting documents ----------
      DOC_KINDS,
      docsOf(r) { return (r && r.attachments) || []; },
      missingDocs(r) { const kinds = this.docsOf(r).map(d => d.kind); return REQUIRED_DOCS.filter(k => !kinds.includes(k)).map(k => DOC_KINDS[k]); },
      docExt(d) { const m = /\.([a-z0-9]{2,4})$/i.exec(d.name); return m ? m[1].toUpperCase() : 'FILE'; },
      canView(d) { return d.type.startsWith('image/') || d.type === 'application/pdf'; },
      fmtSize(n) { return n >= 1048576 ? (n / 1048576).toFixed(1) + ' MB' : Math.max(1, Math.round(n / 1024)) + ' KB'; },
      async viewDoc(d) {
        if (d.type.startsWith('image/')) return this.openPhoto([{ name: d.name + (d.sample ? ' (sample)' : ''), src: d.src }], 0);
        // browsers block opening data: links directly, so open the PDF from a blob
        const blob = await (await fetch(d.src)).blob();
        window.open(URL.createObjectURL(blob), '_blank');
      },
      downloadDoc(d) {
        const a = document.createElement('a');
        a.href = d.src;
        a.download = d.sample ? d.name.replace(/\.\w+$/, '') + ' (sample).svg' : d.name;
        document.body.appendChild(a); a.click(); a.remove();
      },

      // ---------- photo viewer ----------
      openPhoto(photos, i) { this.lightbox = { photos, i }; },
      stepPhoto(d) { const l = this.lightbox; if (l) l.i = (l.i + d + l.photos.length) % l.photos.length; },
      photosOf(r) { return (r.report && r.report.photos) || []; },

      fakeFile(e, target, key) {
        const files = [...(e.target.files || [])].map(f => f.name);
        if (Array.isArray(target[key])) target[key].push(...files); else target[key] = files[0] || '';
      },

      // ---------- notifications (each role sees its own) ----------
      get myNotifications() { return this.db.notifications.filter(n => n.to === this.audience); },
      get unreadCount() { return this.myNotifications.filter(n => !n.read).length; },
      readNotification(n) { n.read = true; if (n.ref && this.byId(n.ref)) this.open(n.ref); },
      markAllRead() { this.myNotifications.forEach(n => n.read = true); },

      // prototype only: the President's part, shared by both dashboards
      simulatePresident(r, action) {
        const title = r.title;
        if (action === 'approve') { this.move(r, 'approved', '', 'pres'); this.pushNotification('The University President approved ' + title + '.', r.id, 'approved', ['ed', 'cd']); }
        if (action === 'return') { this.move(r, 'returned', 'Clarify how this differs from the similar activity flagged by EXTRACK.', 'pres'); this.pushNotification('The University President returned ' + title + '.', r.id, 'returned', ['ed', 'cd']); }
        if (action === 'decline') { this.move(r, 'declined', 'The campus extension budget cannot cover this request this year.', 'pres'); this.pushNotification('The University President declined ' + title + '.', r.id, 'returned', ['ed', 'cd']); }
        this.notify('Simulated: the University President ' + { approve: 'approved', return: 'returned', decline: 'declined' }[action] + ' it.', 'info');
      },
    };
  }

  // ---------- Extension Director ----------
  function extensionDirectorPart() {
    return {
      role: 'ed',
      audience: 'ed',
      campus: 'Balilihan',
      actor: 'ed',
      form: emptyForm(),
      formErrors: {},
      docBusy: false,
      docError: '',
      tabs: [['all', 'All'], ['waiting', 'Waiting for approval'], ['returned', 'Returned'], ['reports', 'Reports overdue'], ['overlaps', 'Overlaps'], ['drafts', 'Drafts']],
      get isDirector() { return this.actor === 'ed'; },
      statusText(r) { return r.status === 'submitted' ? 'With the Campus Director' : STATUS[r.status].label; },

      inTab(tab) {
        const live = this.requests.filter(r => r.status !== 'archived');
        switch (tab) {
          case 'waiting': return this.waitingApproval;
          case 'returned': return this.returnedToMe;
          case 'reports': return this.reportsDue;
          case 'overlaps': return this.overlapOpen;
          case 'drafts': return live.filter(r => r.status === 'draft');
          default: return live;
        }
      },

      // things only the extension office can move forward; approvals belong to the Campus Director and President
      get todo() {
        const items = [];
        this.returnedToMe.forEach(r => {
          const e = this.lastTo(r, 'returned');
          items.push({ r, why: 'Returned by ' + this.person(e && e.by).name, action: 'Edit and resubmit', run: 'edit', tone: 'warn' });
        });
        this.reportsDue.forEach(r => items.push({ r, why: 'Ended ' + fmtDate(r.end) + ', no accomplishment report', action: 'Write report', run: 'report', tone: 'bad' }));
        this.requests.filter(r => r.status === 'approved' && daysBetween(TODAY, r.start) <= 7)
          .forEach(r => items.push({ r, why: 'Approved, starts ' + fmtDate(r.start), action: 'Mark as started', run: 'start', tone: 'muted' }));
        this.requests.filter(r => r.status === 'draft')
          .forEach(r => items.push({ r, why: 'Draft by ' + this.person(r.encodedBy).name, action: 'Review draft', run: 'open', tone: 'muted' }));
        return items;
      },
      doTodo(t) {
        if (t.run === 'edit') this.editRequest(t.r);
        else if (t.run === 'report') this.askReport(t.r);
        else if (t.run === 'start') this.startService(t.r);
        else this.open(t.r.id);
      },

      startService(r) { this.move(r, 'ongoing'); this.notify('Marked as ongoing.'); },

      // ---------- accomplishment reports ----------
      get reportsSoon() { return this.requests.filter(r => r.status === 'ongoing' && r.end >= TODAY && daysBetween(TODAY, r.end) <= 21).sort((a, b) => a.end.localeCompare(b.end)); },
      get submittedReports() {
        const when = r => (this.lastTo(r, 'completed') || this.lastEvent(r)).at;
        return this.requests.filter(r => ['completed', 'archived'].includes(r.status) && r.report).sort((a, b) => when(b).localeCompare(when(a)));
      },
      submittedOn(r) { const e = this.lastTo(r, 'completed'); return e ? fmtDate(e.at.slice(0, 10)) : ''; },
      askReport(r) {
        const p = r.report || {};
        this.modal = { type: 'report', id: r.id, error: '', busy: false, actualStart: p.actualStart || r.start, actualEnd: p.actualEnd || (r.end < TODAY ? r.end : TODAY), actualBudget: p.actualBudget || r.budget, actualBeneficiaries: p.actualBeneficiaries || r.beneficiaries, narrative: p.narrative || '', photos: [...(p.photos || [])] };
      },
      async addPhotos(e) {
        const m = this.modal;
        const files = [...(e.target.files || [])].filter(f => f.type.startsWith('image/'));
        e.target.value = '';
        m.busy = true; m.error = '';
        for (const f of files) {
          try { m.photos.push({ name: f.name, src: await shrinkImage(f) }); } catch (err) { m.error = 'Could not read ' + f.name + '. Choose a JPG or PNG photo.'; }
        }
        m.busy = false;
      },
      removePhoto(i) { this.modal.photos.splice(i, 1); },
      saveReport(submit) {
        const m = this.modal, r = this.byId(m.id);
        if (m.busy) { m.error = 'Wait for the photos to finish loading.'; return; }
        if (!m.narrative.trim() || !m.actualBudget || !m.actualBeneficiaries) { m.error = 'Fill in the actual budget, beneficiaries and what was done.'; return; }
        r.report = { actualStart: m.actualStart, actualEnd: m.actualEnd, actualBudget: Number(m.actualBudget), actualBeneficiaries: Number(m.actualBeneficiaries), narrative: m.narrative.trim(), photos: [...m.photos], encodedBy: this.actor };
        if (submit) {
          this.move(r, 'completed');
          this.pushNotification(this.person('ed').name + ' submitted the accomplishment report for ' + r.title + '.', r.id, 'approved', 'cd');
          this.notify('Accomplishment report submitted. The service is now completed.');
        } else this.notify('Report saved as a draft. The Extension Director can submit it.');
        this.modal = null;
      },

      submitDraft(r) {
        if (!this.isDirector) return;
        this.move(r, 'submitted');
        this.pushNotification(this.person('ed').name + ' submitted ' + r.title + ' for your approval.', r.id, 'request', 'cd');
        this.notify('Submitted to the Campus Director.');
      },
      deleteDraft(r) {
        this.db.requests = this.db.requests.filter(x => x.id !== r.id);
        this.selectedId = null; this.notify('Draft deleted.', 'warn');
      },

      // prototype only: play the Campus Director's part
      simulate(r, who, action) {
        if (who === 'pres') return this.simulatePresident(r, action);
        if (action === 'approve') {
          if (r.overlap && !r.overlap.resolution) Object.assign(r.overlap, { resolution: 'coordinate', resolvedBy: 'cd' });
          this.move(r, 'for_president', '', 'cd');
          this.pushNotification('The Campus Director approved ' + r.title + '. It is now with the President.', r.id, 'approved', 'ed');
          this.pushNotification('The Balilihan Campus Director approved ' + r.title + '. It needs your final approval.', r.id, 'request', 'pres');
        }
        if (action === 'return') { this.move(r, 'returned', 'Please add the expected outcomes per barangay.', 'cd'); this.pushNotification('The Campus Director returned ' + r.title + '.', r.id, 'returned', 'ed'); }
        if (action === 'decline') { this.move(r, 'declined', 'The campus extension budget cannot cover this request this year.', 'cd'); this.pushNotification('The Campus Director declined ' + r.title + '.', r.id, 'returned', 'ed'); }
        this.notify('Simulated: the Campus Director ' + { approve: 'approved', return: 'returned', decline: 'declined' }[action] + ' it.', 'info');
      },

      // ---------- new / edit request ----------
      newRequest() { this.form = emptyForm(); this.formErrors = {}; this.docError = ''; this.selectedId = null; this.view = 'new'; },
      async addDocs(e) {
        const files = [...(e.target.files || [])];
        e.target.value = '';
        this.docBusy = true; this.docError = '';
        for (const f of files) {
          try {
            const isImg = f.type.startsWith('image/');
            if (!isImg && f.size > DOC_LIMIT) { this.docError = f.name + ' is larger than 1 MB. In the prototype, attach a smaller file.'; continue; }
            const src = isImg ? await shrinkImage(f, 1600) : await readAsDataUrl(f);
            this.form.attachments.push({ id: 'a' + Date.now() + Math.random().toString(36).slice(2, 6), kind: guessKind(f.name), name: f.name, type: isImg ? 'image/jpeg' : (f.type || 'application/octet-stream'), size: Math.round(src.length * 0.75), src, by: this.actor, at: nowIso() });
          } catch (err) { this.docError = 'Could not read ' + f.name + '.'; }
        }
        this.docBusy = false;
      },
      removeDoc(i) { this.form.attachments.splice(i, 1); },
      editRequest(r) {
        this.form = { id: r.id, title: r.title, description: r.description, category: r.category, municipality: r.municipality, barangays: [...r.barangays], start: r.start, end: r.end, budget: r.budget, beneficiaries: r.beneficiaries, sectors: [...r.sectors], partners: r.partners, attachments: this.docsOf(r).map(d => ({ ...d })) };
        this.formErrors = {}; this.docError = ''; this.selectedId = null; this.view = 'new';
      },
      toggle(list, v) { const i = list.indexOf(v); i < 0 ? list.push(v) : list.splice(i, 1); },
      get suggestedCategory() { return suggestCategory(this.form.title + ' ' + this.form.description); },
      get formChecks() {
        const f = this.form;
        const draft = { ...f, budget: Number(f.budget || 0), status: 'draft' };
        const hasText = (f.title + f.description).trim().length > 12;
        const matches = hasText && f.barangays.length ? this.overlapCandidates(draft).slice(0, 3) : [];
        return { hasText, matches, budget: f.start ? this.budgetFor(draft) : null };
      },
      overlapCandidates(req) {
        const own = this.requests.filter(r => r.id !== req.id && !['declined', 'draft'].includes(r.status))
          .map(r => ({ code: r.code, campus: 'Balilihan Campus', title: r.title, description: r.description, municipality: r.municipality, barangays: r.barangays, start: r.start, end: r.end, status: r.status }));
        const others = this.db.requests.filter(r => campusOf(r) !== 'Balilihan' && !['declined', 'draft'].includes(r.status))
          .map(r => ({ code: r.code, campus: campusOf(r) + ' Campus', title: r.title, description: r.description, municipality: r.municipality, barangays: r.barangays, start: r.start, end: r.end, status: r.status }));
        return [...others, ...own]
          .map(o => ({ ...o, ...overlapScore(req, o) }))
          .filter(o => o.score >= 40)
          .sort((a, b) => b.score - a.score);
      },
      validate() {
        const f = this.form, e = {};
        if (!f.title.trim()) e.title = 'Add a title.';
        if (f.description.trim().length < 20) e.description = 'Describe what will be done and for whom (at least 20 characters).';
        if (!f.category) e.category = 'Choose a category.';
        if (!f.barangays.length) e.barangays = 'Choose at least one barangay.';
        if (!f.start || !f.end) e.dates = 'Set the start and end dates.';
        else if (f.end < f.start) e.dates = 'The end date is before the start date.';
        if (!(Number(f.budget) > 0)) e.budget = 'Enter the proposed budget.';
        if (!(Number(f.beneficiaries) > 0)) e.beneficiaries = 'Enter the expected number of beneficiaries.';
        this.formErrors = e;
        return !Object.keys(e).length;
      },
      saveForm(submit) {
        if (submit && !this.isDirector) return;
        if (!this.form.title.trim()) { this.formErrors = { title: 'Add a title before saving.' }; return; }
        if (this.docBusy) { this.docError = 'Wait for the files to finish loading.'; return; }
        if (submit && !this.validate()) { this.notify('Some fields need attention.', 'bad'); return; }
        const f = this.form;
        const fields = { title: f.title.trim(), description: f.description.trim(), category: f.category || this.suggestedCategory || 'Others', municipality: f.municipality, barangays: [...f.barangays], start: f.start, end: f.end || f.start, budget: Number(f.budget || 0), beneficiaries: Number(f.beneficiaries || 0), sectors: [...f.sectors], partners: f.partners, attachments: f.attachments.map(d => ({ ...d })) };
        let r = f.id ? this.byId(f.id) : null;
        if (!r) {
          r = { id: 'r' + Date.now(), code: 'BAL-2026-0' + (this.db.nextNo++), requestedBy: 'ed', encodedBy: this.actor, status: 'draft', overlap: null, events: [ev(nowIso(), this.actor, null, 'draft')], remarks: [], report: null };
          this.db.requests.push(r);
        }
        Object.assign(r, fields);
        const top = fields.barangays.length ? this.overlapCandidates(r)[0] : null;
        r.overlap = top && top.score >= 70 ? { score: top.score, match: top, terms: top.terms, resolution: null } : null;
        if (submit) {
          this.move(r, 'submitted');
          this.pushNotification(this.person('ed').name + ' submitted ' + r.title + ' for your approval' + (r.overlap ? '. EXTRACK flagged a possible overlap.' : '.'), r.id, 'request', 'cd');
          this.notify(r.overlap ? 'Submitted to the Campus Director, with the overlap warning attached.' : 'Submitted to the Campus Director.');
        } else this.notify('Saved as a draft.');
        this.form = emptyForm();
        this.view = 'requests'; this.filter = { tab: submit ? 'waiting' : 'drafts', q: '', status: '', category: '' };
        this.open(r.id);
      },
    };
  }

  // ---------- Campus Director ----------
  function campusDirectorPart() {
    return {
      role: 'cd',
      audience: 'cd',
      campus: 'Balilihan',
      actor: 'cd',
      tabs: [['all', 'All'], ['waiting', 'Waiting for you'], ['president', 'With the President'], ['returned', 'Returned'], ['overlaps', 'Overlaps to resolve'], ['running', 'Approved and ongoing']],
      get isDirector() { return this.actor === 'cd'; },
      statusText(r) { return r.status === 'submitted' ? 'Waiting for you' : STATUS[r.status].label; },

      // drafts belong to the extension office and are not shown to the Campus Director
      get visible() { return this.requests.filter(r => !['draft', 'archived'].includes(r.status)); },
      get waitingForMe() { return this.requests.filter(r => r.status === 'submitted').sort((a, b) => this.lastEvent(a).at.localeCompare(this.lastEvent(b).at)); },
      get withPresident() { return this.requests.filter(r => r.status === 'for_president'); },
      get toResolve() { return this.waitingForMe.filter(r => r.overlap && !r.overlap.resolution); },
      get overBudget() { return this.waitingForMe.filter(r => this.budgetFor(r).over); },
      inTab(tab) {
        switch (tab) {
          case 'waiting': return this.waitingForMe;
          case 'president': return this.withPresident;
          case 'returned': return this.visible.filter(r => r.status === 'returned' || r.status === 'declined');
          case 'overlaps': return this.toResolve;
          case 'running': return this.ongoing;
          default: return this.visible;
        }
      },

      // ---------- approve, return, decline ----------
      askDecision(r, kind) {
        if (!this.isDirector) return;
        this.modal = { type: 'decide', kind, id: r.id, remark: '', resolution: r.overlap && !r.overlap.resolution ? '' : null, error: '' };
      },
      confirmDecision() {
        const m = this.modal, r = this.byId(m.id);
        if (m.kind !== 'approve' && !m.remark.trim()) { m.error = 'Add a remark so the Extension Director knows what to change.'; return; }
        if (m.kind === 'approve' && m.resolution === '') { m.error = 'Choose how to handle the possible overlap first.'; return; }
        const remark = m.remark.trim();
        if (m.kind === 'approve') {
          if (r.overlap && m.resolution) Object.assign(r.overlap, { resolution: m.resolution, resolvedBy: 'cd' });
          this.move(r, 'for_president', remark);
          this.pushNotification('The Campus Director approved ' + r.title + '. It is now with the President.', r.id, 'approved', 'ed');
          this.pushNotification('The Balilihan Campus Director approved ' + r.title + '. It needs your final approval.', r.id, 'request', 'pres');
          this.notify('Approved. Sent to the University President for final approval.');
        } else if (m.kind === 'return') {
          this.move(r, 'returned', remark);
          this.pushNotification('The Campus Director returned ' + r.title + ': ' + remark, r.id, 'returned', 'ed');
          this.notify('Returned to the Extension Director with your remark.', 'warn');
        } else {
          this.move(r, 'declined', remark);
          this.pushNotification('The Campus Director declined ' + r.title + '.', r.id, 'returned', 'ed');
          this.notify('Request declined.', 'bad');
        }
        if (remark) r.remarks.push({ at: nowIso(), by: this.actor, body: remark });
        this.modal = null;
      },

      // prototype only: play the Extension Director's and President's part
      simulate(r, who, action) {
        if (who === 'pres') return this.simulatePresident(r, action);
        if (action === 'resubmit') {
          this.move(r, 'submitted', 'Revised as requested.', 'ed');
          this.pushNotification(this.person('ed').name + ' resubmitted ' + r.title + '.', r.id, 'request', 'cd');
          this.notify('Simulated: the Extension Director resubmitted it.', 'info');
        }
      },
      simulateNewRequest() {
        const no = this.db.nextNo++;
        const r = {
          id: 'r' + Date.now(), code: 'BAL-2026-0' + no, requestedBy: 'ed', encodedBy: 'asst',
          title: 'Hog Raising and Feed Formulation Seminar', description: 'Seminar on backyard hog raising, low-cost feed formulation and disease prevention for farmers.',
          category: 'Agriculture & Fisheries', municipality: 'Balilihan', barangays: ['Maslog'], start: '2026-12-09', end: '2026-12-10', budget: 22000, beneficiaries: 30, sectors: ['Farmers'], partners: 'Office of the Municipal Veterinarian', attachments: [sampleDoc('letter', 'Maslog_request.pdf'), sampleDoc('proposal', 'Hog_raising_seminar_proposal.pdf')],
          status: 'submitted', overlap: null, events: [ev(nowIso(), 'asst', null, 'draft'), ev(nowIso(), 'ed', 'draft', 'submitted')], remarks: [], report: null,
        };
        this.db.requests.push(r);
        this.pushNotification(this.person('ed').name + ' submitted ' + r.title + ' for your approval.', r.id, 'request', 'cd');
        this.notify('Simulated: a new request arrived from the Extension Director.', 'info');
      },
    };
  }

  // ---------- University President ----------
  function presidentPart() {
    const sum = (arr, f) => arr.reduce((t, x) => t + f(x), 0);
    const RUNNING = ['approved', 'ongoing'], DONE = ['completed', 'archived'];
    return {
      role: 'pres',
      audience: 'pres',
      actor: 'pres',
      campus: null,
      tabs: [['waiting', 'Waiting for you'], ['all', 'All'], ['running', 'Approved and ongoing'], ['completed', 'Completed'], ['stopped', 'Returned or declined']],
      get isDirector() { return true; },
      statusText(r) { return { submitted: 'With the Campus Director', for_president: 'Waiting for you' }[r.status] || STATUS[r.status].label; },

      get visible() { return this.requests.filter(r => !['draft', 'archived'].includes(r.status)); },
      get queue() { return this.requests.filter(r => r.status === 'for_president').sort((a, b) => this.lastEvent(a).at.localeCompare(this.lastEvent(b).at)); },
      inTab(tab) {
        switch (tab) {
          case 'waiting': return this.queue;
          case 'running': return this.visible.filter(r => RUNNING.includes(r.status));
          case 'completed': return this.requests.filter(r => DONE.includes(r.status));
          case 'stopped': return this.visible.filter(r => r.status === 'returned' || r.status === 'declined');
          default: return this.visible;
        }
      },
      inYear(r) { return r.start.startsWith(String(this.db.year)); },

      // ---------- university-wide figures for the year ----------
      get totals() {
        const rs = this.requests.filter(r => this.inYear(r) && [...RUNNING, ...DONE].includes(r.status));
        const done = rs.filter(r => DONE.includes(r.status));
        const budgets = CAMPUSES.map(c => this.budgetOf(c));
        const alloc = sum(budgets, b => b.allocation), used = sum(budgets, b => b.spent + b.committed);
        return {
          services: rs.length, completed: done.length,
          reached: sum(done, r => r.report ? r.report.actualBeneficiaries : 0),
          barangays: new Set(rs.flatMap(r => r.barangays.map(b => b + ', ' + r.municipality))).size,
          alloc, used, usedPct: alloc ? Math.round(used / alloc * 100) : 0,
          overdue: this.requests.filter(r => r.status === 'ongoing' && r.end < TODAY).length,
        };
      },
      get campusRows() {
        return CAMPUSES.map(c => {
          const rs = this.requests.filter(r => campusOf(r) === c && this.inYear(r));
          const b = this.budgetOf(c);
          const used = b.spent + b.committed;
          return {
            c,
            services: rs.filter(r => [...RUNNING, ...DONE].includes(r.status)).length,
            running: rs.filter(r => RUNNING.includes(r.status)).length,
            completed: rs.filter(r => DONE.includes(r.status)).length,
            reached: sum(rs.filter(r => DONE.includes(r.status)), r => r.report ? r.report.actualBeneficiaries : 0),
            used, alloc: b.allocation, pct: b.allocation ? Math.round(used / b.allocation * 100) : 0,
            overdue: this.requests.filter(r => campusOf(r) === c && r.status === 'ongoing' && r.end < TODAY).length,
            waiting: this.requests.filter(r => campusOf(r) === c && r.status === 'for_president').length,
          };
        });
      },
      // overlaps where the similar work belongs to a different campus
      get crossOverlaps() {
        // the same pair can be flagged from both campuses' sides; list it once
        const seen = new Set();
        return this.requests.filter(r => r.overlap && r.overlap.match && r.overlap.match.campus !== this.campusName(r) && !['declined', 'draft'].includes(r.status))
          .filter(r => { const k = [r.code, r.overlap.match.code].sort().join('|'); if (seen.has(k)) return false; seen.add(k); return true; });
      },

      // ---------- final approval ----------
      askDecision(r, kind) { this.modal = { type: 'decide', kind, id: r.id, remark: '', resolution: null, error: '' }; },
      confirmDecision() {
        const m = this.modal, r = this.byId(m.id), remark = m.remark.trim();
        if (m.kind !== 'approve' && !remark) { m.error = 'Add a remark so the campus knows what to change.'; return; }
        // only Balilihan has working director accounts in the prototype
        const to = campusOf(r) === 'Balilihan' ? ['ed', 'cd'] : [];
        if (m.kind === 'approve') {
          this.move(r, 'approved', remark);
          this.pushNotification('The University President approved ' + r.title + '.', r.id, 'approved', to);
          this.notify('Approved. The extension office can now start the service.');
        } else if (m.kind === 'return') {
          this.move(r, 'returned', remark);
          this.pushNotification('The University President returned ' + r.title + ': ' + remark, r.id, 'returned', to);
          this.notify('Returned to the ' + this.campusName(r) + ' Extension Director.', 'warn');
        } else {
          this.move(r, 'declined', remark);
          this.pushNotification('The University President declined ' + r.title + '.', r.id, 'returned', to);
          this.notify('Request declined.', 'bad');
        }
        if (remark) r.remarks.push({ at: nowIso(), by: this.actor, body: remark });
        this.modal = null;
      },
    };
  }

  document.addEventListener('alpine:init', () => {
    Alpine.data('extensionDirector', () => compose(shared(), extensionDirectorPart()));
    Alpine.data('campusDirector', () => compose(shared(), campusDirectorPart()));
    Alpine.data('universityPresident', () => compose(shared(), presidentPart()));
  });
})();

/**
 * ============================================================================
 *  UNIVERSITY MANAGEMENT SYSTEM  —  Multi-Country Education Consultancy CRM
 *  Google Sheets + Apps Script                                   Version 1.0
 * ============================================================================
 *  HOW TO INSTALL
 *    1. Import the spreadsheet (UMS_Template.xlsx) into Google Sheets
 *       (or start from an empty Google Sheet — the script builds everything).
 *    2. Extensions → Apps Script → paste this whole file as Code.gs → Save.
 *    3. Run  setupUniversityManagementSystem  → Authorize.
 *    4. Reload the spreadsheet tab. Use the "🎓 University System" menu.
 *
 *  setupUniversityManagementSystem() is SAFE TO RUN AGAIN at any time.
 *  It never deletes database rows. It only creates what is missing and
 *  re-draws the dashboards.
 * ============================================================================
 */

var UMS = {
  VERSION: '1.0.0',
  SAMPLE_FLAG: 'SAMPLE / DEMO DATA',
  BUTTON_TAG: 'UMS_BUTTON',
  FONT: 'Arial',

  SHEETS: {
    HOME: 'HOME',
    US: 'University Search',
    PS: 'Program Search',
    DE: 'University Data Entry',
    PV: 'Print View',
    SETTINGS: '01_Settings',
    COUNTRIES: '02_Countries',
    UNIVERSITIES: '03_Universities',
    CAMPUSES: '04_Campuses',
    INTAKES: '05_Intakes',
    ROUNDS: '05b_Application_Rounds',
    PROGRAMS: '06_Programs',
    ACADEMIC: '07_Academic_Requirements',
    LANGUAGE: '08_Language_Requirements',
    FEES: '09_Fees',
    SCHOLARSHIPS: '10_Scholarships',
    TIERS: '11_Scholarship_Tiers',
    DOCUMENTS: '12_Document_Requirements',
    VISA: '13_Visa_Information',
    SOURCES: '14_Sources',
    AUDIT: '15_Audit_Log',
    CSR: '16_Country_Specific_Requirements',
    LISTS: '_Lists'
  },

  // Colour palette — "education CRM" navy / blue / teal
  C: {
    NAVY: '#0F2A47', NAVY2: '#163A5F', BLUE: '#1D4ED8', BLUE_L: '#DBEAFE', BLUE_XL: '#EFF6FF',
    TEAL: '#0F766E', TEAL_L: '#CCFBF1', AMBER: '#B45309', AMBER_L: '#FEF3C7',
    PURPLE: '#6D28D9', PURPLE_L: '#EDE9FE', ROSE: '#BE123C', ROSE_L: '#FFE4E6',
    GREEN: '#15803D', GREEN_L: '#DCFCE7',
    PAGE: '#EEF2F7', CARD: '#FFFFFF', LINE: '#CBD5E1', LINE_L: '#E2E8F0',
    TEXT: '#0F172A', MUTED: '#64748B', LABEL: '#475569', HEAD_BG: '#F1F5F9',
    INPUT: '#FFFFFF', INPUT_BORDER: '#93C5FD', RO: '#F1F5F9', WHITE: '#FFFFFF',
    SUB: '#BFD3EA', ZEBRA: '#F8FAFC'
  },

  STATUS_STYLE: {
    'Verified':             { bg: '#DCFCE7', fc: '#166534' },
    'Pending Verification': { bg: '#FEF9C3', fc: '#854D0E' },
    'Need Update':          { bg: '#FFEDD5', fc: '#9A3412' },
    'Expired':              { bg: '#FEE2E2', fc: '#991B1B' },
    'Archived':             { bg: '#E5E7EB', fc: '#374151' }
  }
};

/* ---------------------------------------------------------------------------
 *  DROPDOWN LISTS  (stored in 01_Settings, columns D onward — add values
 *  there and every dropdown picks them up automatically)
 * ------------------------------------------------------------------------- */
var LISTS = [
  ['STATUS', 'Status', ['Verified', 'Pending Verification', 'Need Update', 'Expired', 'Archived']],
  ['DEGREE', 'Degree Level', ["Foundation", "Diploma", "Bachelor's", "Master's", "PhD", "Language Course", "Certificate", "Other"]],
  ['INTAKE_TERM', 'Intake Term', ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December', 'Spring', 'Summer', 'Fall', 'Winter', 'Custom']],
  ['INTAKE_TYPE', 'Intake Type', ['Main Intake', 'Secondary Intake', 'Special Intake', 'Rolling Intake', 'Custom']],
  ['ROUND', 'Application Round', ['Round 1', 'Round 2', 'Round 3', 'Round 4', 'Round 5', 'Early Application', 'Regular Application', 'Rolling Admission', 'Custom']],
  ['LANG_TEST', 'Language Test', ['IELTS', 'TOEFL iBT', 'TOEFL PBT', 'PTE Academic', 'Duolingo', 'Cambridge', 'TOPIK', 'OET', 'CELPIP', 'CAEL', 'SELT', 'Other']],
  ['FEE_TYPE', 'Fee Type', ['Application Fee', 'Admission Fee', 'Tuition Fee', 'Registration Fee', 'Student Service Fee', 'Insurance', 'Deposit', 'Other']],
  ['CURRENCY', 'Currency', ['KRW', 'AUD', 'CAD', 'GBP', 'USD', 'MYR', 'EUR', 'JPY', 'NZD', 'Other']],
  ['PERIOD', 'Fee Period', ['Per Semester', 'Per Year', 'Per Trimester', 'Per Quarter', 'Per Credit', 'Per Module', 'Per Course', 'Total Program', 'One-time', 'Other']],
  ['SCH_TYPE', 'Scholarship Type', ['Percentage', 'Fixed Amount', 'Tuition Waiver', 'Living Allowance', 'Accommodation', 'Other']],
  ['SCH_MODE', 'Award Mode', ['Automatic', 'Application Required']],
  ['UNI_TYPE', 'University Type', ['Public', 'Private', 'National', 'Research', 'Technical', 'College', 'Other']],
  ['STUDY_MODE', 'Study Mode', ['Full-time', 'Part-time', 'Online', 'Hybrid']],
  ['LANG_INSTR', 'Language of Instruction', ['English', 'Korean', 'Japanese', 'German', 'French', 'Malay', 'Bilingual', 'Other']],
  ['DOC', 'Document', ['Passport', 'Academic Certificate', 'Transcript', 'IELTS / English Test Report', 'Statement of Purpose (SOP)', 'Letter of Recommendation (LOR)', 'CV / Resume', 'Bank Statement', 'Sponsor Letter', 'Photograph', 'Portfolio', 'Research Proposal', 'Birth Certificate', 'Medical Certificate', 'Police Clearance', 'Apostille / Attestation', 'Other']],
  ['DOC_REQ', 'Requirement Level', ['Mandatory', 'Optional', 'Conditional']],
  ['SOURCE_TYPE', 'Source Type', ['Official Website', 'Admission Page', 'Scholarship Page', 'Government Source', 'Fee Page', 'Brochure / PDF', 'Email from University', 'Other']],
  ['CSR_CAT', 'Requirement Category', ['Academic', 'Language', 'Financial', 'Visa', 'Document Attestation', 'Health / Insurance', 'Interview', 'Other']],
  ['YESNO', 'Yes / No', ['Yes', 'No']],
  ['ANYYESNO', 'Any / Yes / No', ['Any', 'Yes', 'No']],
  ['REGION', 'Region', ['East Asia', 'South-East Asia', 'South Asia', 'Oceania', 'North America', 'Europe', 'Middle East', 'Africa', 'Latin America', 'Other']],
  ['SCOPE', 'Scope', ['Country', 'University']],
  ['SORT', 'Sort Results By', ['Country', 'University', 'Program', 'Tuition (low → high)', 'Tuition (high → low)', 'Nearest Deadline']],
  ['PROTECT', 'Protection Mode', ['Warning', 'Strict']]
];

/* ---------------------------------------------------------------------------
 *  SETTINGS  (key / value in 01_Settings columns A:B)
 * ------------------------------------------------------------------------- */
var SETTINGS_DEFAULTS = [
  ['System Name', 'UNIVERSITY MANAGEMENT SYSTEM'],
  ['Subtitle', 'International Education Consultancy'],
  ['Consultancy Name', 'Your Consultancy Name'],
  ['Deadline Warning Days (Orange)', 7],
  ['Deadline Notice Days (Yellow)', 30],
  ['Show Passed Deadlines For (days)', 14],
  ['Home Deadline Rows', 20],
  ['Program Search Max Results', 500],
  ['Auto Search On Edit', 'Yes'],
  ['Hide Database Sheets', 'Yes'],
  ['Protection Mode', 'Warning'],
  ['Admin Emails (comma separated)', ''],
  ['Sample Data Loaded', 'No'],
  ['Version', UMS.VERSION],
  ['Last Setup', '']
];

/* ---------------------------------------------------------------------------
 *  DATABASE SCHEMAS
 *  [key, header, type]   type: text | date | num | url | list:KEY
 * ------------------------------------------------------------------------- */
var COMMON_COLS = [
  ['status', 'Status', 'list:STATUS'],
  ['source', 'Source URL', 'url'],
  ['lastChecked', 'Last Checked', 'date'],
  ['dataFlag', 'Data Flag', 'text'],
  ['created', 'Created Date', 'date'],
  ['updated', 'Last Updated', 'date'],
  ['updatedBy', 'Updated By', 'text']
];

var SCHEMA = {};
SCHEMA[UMS.SHEETS.COUNTRIES] = [
  ['id', 'Country ID'], ['country', 'Country Name'], ['region', 'Region', 'list:REGION'],
  ['currency', 'Default Currency', 'list:CURRENCY'], ['language', 'Official Language(s)'],
  ['active', 'Active', 'list:YESNO'], ['notes', 'Notes']
].concat(COMMON_COLS);

SCHEMA[UMS.SHEETS.UNIVERSITIES] = [
  ['id', 'University ID'], ['countryId', 'Country ID'], ['country', 'Country'], ['name', 'University Name'],
  ['city', 'City'], ['type', 'University Type', 'list:UNI_TYPE'], ['website', 'Official Website', 'url'],
  ['intlWebsite', 'International Website', 'url'], ['logo', 'Logo URL', 'url'], ['ranking', 'Ranking'],
  ['accreditation', 'Accreditation'], ['notes', 'Notes']
].concat(COMMON_COLS);

SCHEMA[UMS.SHEETS.CAMPUSES] = [
  ['id', 'Campus ID'], ['uniId', 'University ID'], ['country', 'Country'], ['university', 'University'],
  ['campus', 'Campus Name'], ['city', 'City'], ['address', 'Address'], ['notes', 'Notes']
].concat(COMMON_COLS);

SCHEMA[UMS.SHEETS.INTAKES] = [
  ['id', 'Intake ID'], ['uniId', 'University ID'], ['country', 'Country'], ['university', 'University'],
  ['intake', 'Intake Name'], ['term', 'Intake Term', 'list:INTAKE_TERM'], ['year', 'Intake Year', 'num'],
  ['intakeType', 'Intake Type', 'list:INTAKE_TYPE'], ['degree', 'Applies To Degree', 'list:DEGREE'],
  ['campus', 'Campus'], ['rolling', 'Rolling Admission', 'list:YESNO'], ['notes', 'Notes']
].concat(COMMON_COLS);

SCHEMA[UMS.SHEETS.ROUNDS] = [
  ['id', 'Round ID'], ['intakeId', 'Intake ID'], ['uniId', 'University ID'], ['country', 'Country'],
  ['university', 'University'], ['intake', 'Intake'], ['round', 'Application Round', 'list:ROUND'],
  ['appStart', 'Application Start', 'date'], ['appEnd', 'Application End', 'date'],
  ['interviewStart', 'Interview Start', 'date'], ['interviewEnd', 'Interview End', 'date'],
  ['result', 'Result Date', 'date'], ['depositDeadline', 'Deposit Deadline', 'date'],
  ['enrollDeadline', 'Enrollment Deadline', 'date'], ['notes', 'Notes']
].concat(COMMON_COLS);

SCHEMA[UMS.SHEETS.PROGRAMS] = [
  ['id', 'Program ID'], ['uniId', 'University ID'], ['country', 'Country'], ['university', 'University'],
  ['campus', 'Campus'], ['degree', 'Degree Level', 'list:DEGREE'], ['faculty', 'Faculty'],
  ['department', 'Department'], ['program', 'Program Name'], ['major', 'Major / Field'],
  ['duration', 'Duration'], ['studyMode', 'Study Mode', 'list:STUDY_MODE'],
  ['language', 'Language of Instruction', 'list:LANG_INSTR'], ['intakes', 'Intakes Offered'], ['notes', 'Notes']
].concat(COMMON_COLS);

SCHEMA[UMS.SHEETS.ACADEMIC] = [
  ['id', 'Requirement ID'], ['uniId', 'University ID'], ['programId', 'Program ID (or ALL)'], ['country', 'Country'],
  ['university', 'University'], ['program', 'Program'], ['degree', 'Degree Level', 'list:DEGREE'],
  ['minGpa', 'Minimum GPA', 'num'], ['gpaScale', 'GPA Scale'], ['prevDegree', 'Previous Degree Required'],
  ['background', 'Required Background'], ['minPercent', 'Minimum Percentage', 'num'],
  ['workExp', 'Work Experience'], ['entranceExam', 'Entrance Exam (GRE/GMAT/SAT)'],
  ['interview', 'Portfolio / Interview'], ['other', 'Other Requirements']
].concat(COMMON_COLS);

SCHEMA[UMS.SHEETS.LANGUAGE] = [
  ['id', 'Language Req ID'], ['uniId', 'University ID'], ['programId', 'Program ID (or ALL)'], ['country', 'Country'],
  ['university', 'University'], ['program', 'Program'], ['degree', 'Degree Level', 'list:DEGREE'],
  ['test', 'Test', 'list:LANG_TEST'], ['minScore', 'Minimum Score'], ['subScore', 'Sub-score Requirement'],
  ['waiver', 'Waiver Condition'], ['notes', 'Notes']
].concat(COMMON_COLS);

SCHEMA[UMS.SHEETS.FEES] = [
  ['id', 'Fee ID'], ['uniId', 'University ID'], ['programId', 'Program ID (or ALL)'], ['country', 'Country'],
  ['university', 'University'], ['program', 'Program'], ['degree', 'Degree Level', 'list:DEGREE'],
  ['feeType', 'Fee Type', 'list:FEE_TYPE'], ['amount', 'Amount', 'num'], ['currency', 'Currency', 'list:CURRENCY'],
  ['period', 'Period', 'list:PERIOD'], ['notes', 'Notes']
].concat(COMMON_COLS);

SCHEMA[UMS.SHEETS.SCHOLARSHIPS] = [
  ['id', 'Scholarship ID'], ['uniId', 'University ID'], ['programId', 'Program ID (or ALL)'], ['country', 'Country'],
  ['university', 'University'], ['program', 'Program'], ['degree', 'Degree Level', 'list:DEGREE'],
  ['name', 'Scholarship Name'], ['schType', 'Scholarship Type', 'list:SCH_TYPE'], ['mode', 'Award Mode', 'list:SCH_MODE'],
  ['eligibility', 'Eligibility'], ['minGpa', 'Minimum GPA', 'num'], ['percent', 'Scholarship %', 'num'],
  ['amount', 'Amount', 'num'], ['currency', 'Currency', 'list:CURRENCY'], ['duration', 'Duration'],
  ['renewal', 'Renewal Requirement'], ['notes', 'Notes']
].concat(COMMON_COLS);

SCHEMA[UMS.SHEETS.TIERS] = [
  ['id', 'Tier ID'], ['schId', 'Scholarship ID'], ['uniId', 'University ID'], ['country', 'Country'],
  ['university', 'University'], ['scholarship', 'Scholarship Name'], ['tier', 'Tier Name'],
  ['minGpa', 'Min GPA', 'num'], ['ielts', 'IELTS', 'num'], ['toefl', 'TOEFL iBT', 'num'], ['pte', 'PTE', 'num'],
  ['topik', 'TOPIK', 'num'], ['otherTest', 'Other Test'], ['otherScore', 'Other Score'],
  ['percent', 'Scholarship %', 'num'], ['amount', 'Amount', 'num'], ['currency', 'Currency', 'list:CURRENCY'], ['notes', 'Notes']
].concat(COMMON_COLS);

SCHEMA[UMS.SHEETS.DOCUMENTS] = [
  ['id', 'Document Req ID'], ['uniId', 'University ID'], ['programId', 'Program ID (or ALL)'], ['country', 'Country'],
  ['university', 'University'], ['program', 'Program'], ['degree', 'Degree Level', 'list:DEGREE'],
  ['document', 'Document', 'list:DOC'], ['level', 'Requirement Level', 'list:DOC_REQ'],
  ['spec', 'Specification'], ['notes', 'Notes']
].concat(COMMON_COLS);

SCHEMA[UMS.SHEETS.VISA] = [
  ['id', 'Visa ID'], ['countryId', 'Country ID'], ['country', 'Country'], ['visaType', 'Visa Type'],
  ['financial', 'Financial Proof'], ['processing', 'Processing Time'], ['workRights', 'Work Rights'],
  ['url', 'Official Visa URL', 'url'], ['notes', 'Notes']
].concat(COMMON_COLS);

SCHEMA[UMS.SHEETS.SOURCES] = [
  ['id', 'Source ID'], ['uniId', 'University ID'], ['country', 'Country'], ['university', 'University'],
  ['sourceType', 'Source Type', 'list:SOURCE_TYPE'], ['title', 'Title'], ['url', 'URL', 'url'],
  ['checkedOn', 'Last Checked On', 'date'], ['checkedBy', 'Checked By'], ['notes', 'Notes']
].concat(COMMON_COLS);

SCHEMA[UMS.SHEETS.AUDIT] = [
  ['ts', 'Timestamp', 'date'], ['user', 'User'], ['action', 'Action'], ['sheet', 'Sheet'], ['recordId', 'Record ID'],
  ['country', 'Country'], ['university', 'University'], ['program', 'Program'], ['oldValue', 'Old Value'], ['newValue', 'New Value']
];

SCHEMA[UMS.SHEETS.CSR] = [
  ['id', 'Requirement ID'], ['countryId', 'Country ID'], ['country', 'Country'], ['uniId', 'University ID (or ALL)'],
  ['university', 'University'], ['degree', 'Degree Level', 'list:DEGREE'], ['category', 'Category', 'list:CSR_CAT'],
  ['requirement', 'Requirement'], ['details', 'Details'], ['mandatory', 'Mandatory', 'list:YESNO'], ['notes', 'Notes']
].concat(COMMON_COLS);

var DB_ORDER = [
  UMS.SHEETS.COUNTRIES, UMS.SHEETS.UNIVERSITIES, UMS.SHEETS.CAMPUSES, UMS.SHEETS.INTAKES, UMS.SHEETS.ROUNDS,
  UMS.SHEETS.PROGRAMS, UMS.SHEETS.ACADEMIC, UMS.SHEETS.LANGUAGE, UMS.SHEETS.FEES, UMS.SHEETS.SCHOLARSHIPS,
  UMS.SHEETS.TIERS, UMS.SHEETS.DOCUMENTS, UMS.SHEETS.VISA, UMS.SHEETS.SOURCES, UMS.SHEETS.AUDIT, UMS.SHEETS.CSR
];
var UI_ORDER = [UMS.SHEETS.HOME, UMS.SHEETS.US, UMS.SHEETS.PS, UMS.SHEETS.DE, UMS.SHEETS.PV];

// Tables that carry a Status column (used for data-quality counts)
var STATUS_TABLES = DB_ORDER.filter(function (n) { return n !== UMS.SHEETS.AUDIT; });

// Known ISO-style codes. The user asked for "UK" (not GB) for the United Kingdom.
var COUNTRY_CODES = {
  'south korea': 'KR', 'korea': 'KR', 'republic of korea': 'KR', 'australia': 'AU', 'canada': 'CA',
  'united kingdom': 'UK', 'uk': 'UK', 'england': 'UK', 'scotland': 'UK', 'usa': 'US', 'united states': 'US',
  'united states of america': 'US', 'malaysia': 'MY', 'japan': 'JP', 'new zealand': 'NZ', 'germany': 'DE',
  'ireland': 'IE', 'france': 'FR', 'netherlands': 'NL', 'sweden': 'SE', 'finland': 'FI', 'denmark': 'DK',
  'norway': 'NO', 'italy': 'IT', 'spain': 'ES', 'portugal': 'PT', 'poland': 'PL', 'hungary': 'HU',
  'austria': 'AT', 'switzerland': 'CH', 'belgium': 'BE', 'czech republic': 'CZ', 'china': 'CN',
  'taiwan': 'TW', 'hong kong': 'HK', 'singapore': 'SG', 'thailand': 'TH', 'indonesia': 'ID',
  'philippines': 'PH', 'vietnam': 'VN', 'india': 'IN', 'bangladesh': 'BD', 'turkey': 'TR', 'cyprus': 'CY',
  'united arab emirates': 'AE', 'uae': 'AE', 'saudi arabia': 'SA', 'qatar': 'QA', 'lithuania': 'LT',
  'latvia': 'LV', 'estonia': 'EE', 'malta': 'MT', 'russia': 'RU', 'georgia': 'GE', 'mauritius': 'MU',
  'south africa': 'ZA', 'brazil': 'BR', 'mexico': 'MX'
};

// Score ranges used to validate language scores and tiers
var SCORE_RANGES = {
  'IELTS': [0, 9], 'TOEFL iBT': [0, 120], 'TOEFL PBT': [310, 677], 'PTE Academic': [10, 90],
  'Duolingo': [10, 160], 'Cambridge': [80, 230], 'TOPIK': [1, 6], 'CELPIP': [1, 12], 'CAEL': [10, 90]
};


/* ============================================================================
 *  CORE UTILITIES — spreadsheet access, tables, IDs, duplicates, audit
 * ========================================================================== */

var _CACHE = {};
var _SETTINGS = null;

function ss_() { return SpreadsheetApp.getActiveSpreadsheet(); }

function getSheet_(name) {
  var sh = ss_().getSheetByName(name);
  if (!sh) throw new UmsError('Sheet "' + name + '" is missing. Please run  setupUniversityManagementSystem()  from the menu.');
  return sh;
}

/** A user-facing error (shown as a friendly message, not a stack trace). */
function UmsError(message) { this.name = 'UmsError'; this.message = message; }
UmsError.prototype = Object.create(Error.prototype);
UmsError.prototype.constructor = UmsError;

function invalidate_(name) { if (name) delete _CACHE[name]; else _CACHE = {}; }

function schemaKeys_(name) { return (SCHEMA[name] || []).map(function (c) { return c[0]; }); }
function schemaHeader_(name, key) {
  var s = SCHEMA[name] || [];
  for (var i = 0; i < s.length; i++) if (s[i][0] === key) return s[i][1];
  return null;
}

/**
 * Reads a database sheet once per execution and returns
 *  { name, sheet, headers, idx{key:colIndex0}, objs:[{..., _row}] }
 * Columns are located by HEADER TEXT, so users may reorder columns safely.
 */
function readTable_(name) {
  if (_CACHE[name]) return _CACHE[name];
  var sh = getSheet_(name);
  var lr = sh.getLastRow(), lc = sh.getLastColumn();
  var values = (lr > 0 && lc > 0) ? sh.getRange(1, 1, lr, lc).getValues() : [[]];
  var headers = values[0].map(function (h) { return String(h).trim(); });
  var idx = {};
  (SCHEMA[name] || []).forEach(function (c) {
    var i = headers.indexOf(c[1]);
    if (i >= 0) idx[c[0]] = i;
  });
  var firstKey = (SCHEMA[name] || [['id']])[0][0];
  var objs = [];
  for (var r = 1; r < values.length; r++) {
    var row = values[r];
    var key0 = idx[firstKey] !== undefined ? row[idx[firstKey]] : row[0];
    if (key0 === '' || key0 === null) continue;
    var o = { _row: r + 1 };
    for (var k in idx) o[k] = row[idx[k]];
    objs.push(o);
  }
  var t = { name: name, sheet: sh, headers: headers, idx: idx, objs: objs, lastRow: lr };
  _CACHE[name] = t;
  return t;
}

function ensureCapacity_(sh, neededRow) {
  var max = sh.getMaxRows();
  if (neededRow > max) sh.insertRowsAfter(max, Math.max(200, neededRow - max + 50));
}

/** Appends a record object to a table (columns matched by header). */
function appendRecord_(name, obj) {
  var t = readTable_(name);
  var row = t.headers.map(function () { return ''; });
  for (var k in obj) if (t.idx[k] !== undefined) row[t.idx[k]] = (obj[k] === undefined || obj[k] === null) ? '' : obj[k];
  var target = Math.max(t.lastRow, 1) + 1;
  ensureCapacity_(t.sheet, target);
  t.sheet.getRange(target, 1, 1, row.length).setValues([row]);
  // keep the cached table in sync instead of re-reading the sheet
  var o = { _row: target };
  for (var key in t.idx) o[key] = row[t.idx[key]];
  t.objs.push(o);
  t.lastRow = Math.max(t.lastRow, target);
  return target;
}

/** Writes changed fields to an existing row. Returns {changed:{key:[old,new]}}. */
function updateRow_(name, rowNum, changes) {
  var t = readTable_(name);
  var rng = t.sheet.getRange(rowNum, 1, 1, t.headers.length);
  var row = rng.getValues()[0];
  var changed = {};
  for (var k in changes) {
    if (t.idx[k] === undefined) continue;
    var oldV = row[t.idx[k]], newV = changes[k];
    if (newV === undefined || newV === null) newV = '';
    if (sameValue_(oldV, newV)) continue;
    row[t.idx[k]] = newV;
    changed[k] = [oldV, newV];
  }
  if (Object.keys(changed).length) {
    rng.setValues([row]);
    var cached = first_(t.objs, function (o) { return o._row === rowNum; });
    if (cached) for (var ck in changed) cached[ck] = changed[ck][1];
  }
  return changed;
}

function sameValue_(a, b) {
  if (a instanceof Date && b instanceof Date) return a.getTime() === b.getTime();
  if (a instanceof Date || b instanceof Date) return false;
  if (typeof a === 'number' || typeof b === 'number') {
    if (a === '' || b === '') return a === b;
    return Number(a) === Number(b);
  }
  return String(a) === String(b);
}

function findBy_(name, key, value) {
  var objs = readTable_(name).objs;
  for (var i = 0; i < objs.length; i++) if (String(objs[i][key]) === String(value)) return objs[i];
  return null;
}
function filterBy_(name, fn) { return readTable_(name).objs.filter(fn); }

/* ---------------------------------------------------------------------------
 *  SETTINGS
 * ------------------------------------------------------------------------- */
function getSettings_() {
  if (_SETTINGS) return _SETTINGS;
  var s = {};
  SETTINGS_DEFAULTS.forEach(function (d) { s[d[0]] = d[1]; });
  var sh = ss_().getSheetByName(UMS.SHEETS.SETTINGS);
  if (sh && sh.getLastRow() > 1) {
    var v = sh.getRange(2, 1, sh.getLastRow() - 1, 2).getValues();
    v.forEach(function (r) { if (String(r[0]).trim()) s[String(r[0]).trim()] = r[1]; });
  }
  _SETTINGS = s;
  return s;
}
function setting_(key) { return getSettings_()[key]; }
function setSetting_(key, value) {
  var sh = getSheet_(UMS.SHEETS.SETTINGS);
  var lr = Math.max(sh.getLastRow(), 1);
  var keys = sh.getRange(1, 1, lr, 1).getValues();
  for (var i = 0; i < keys.length; i++) {
    if (String(keys[i][0]).trim() === key) { sh.getRange(i + 1, 2).setValue(value); _SETTINGS = null; return; }
  }
  sh.getRange(lr + 1, 1, 1, 2).setValues([[key, value]]);
  _SETTINGS = null;
}

/** Reads a dropdown list (01_Settings list columns). */
function getList_(key) {
  var cache = _CACHE['__lists'];
  if (!cache) {
    cache = {};
    var sh = ss_().getSheetByName(UMS.SHEETS.SETTINGS);
    if (sh) {
      var lr = Math.max(sh.getLastRow(), 2);
      var v = sh.getRange(1, 4, lr, LISTS.length).getValues();
      LISTS.forEach(function (l, j) {
        var out = [];
        for (var r = 1; r < v.length; r++) if (String(v[r][j]).trim() !== '') out.push(String(v[r][j]).trim());
        cache[l[0]] = out;
      });
    }
    _CACHE['__lists'] = cache;
  }
  if (cache[key] && cache[key].length) return cache[key];
  for (var i = 0; i < LISTS.length; i++) if (LISTS[i][0] === key) return LISTS[i][2];
  return [];
}
function listColumnLetter_(key) {
  for (var i = 0; i < LISTS.length; i++) if (LISTS[i][0] === key) return colLetter_(4 + i);
  return null;
}
function listRangeA1_(key) {
  var L = listColumnLetter_(key);
  return "'" + UMS.SHEETS.SETTINGS + "'!$" + L + '$2:$' + L + '$300';
}

/* ---------------------------------------------------------------------------
 *  GENERIC HELPERS
 * ------------------------------------------------------------------------- */
function colLetter_(n) {
  var s = '';
  while (n > 0) { var m = (n - 1) % 26; s = String.fromCharCode(65 + m) + s; n = Math.floor((n - 1) / 26); }
  return s;
}
function norm_(s) { return String(s === null || s === undefined ? '' : s).toLowerCase().replace(/[^a-z0-9À-￿]+/g, ' ').trim(); }
function str_(v) { return (v === null || v === undefined) ? '' : String(v).trim(); }
function blank_(v) { return v === null || v === undefined || String(v).trim() === ''; }
function pad_(n, w) { var s = String(n); while (s.length < w) s = '0' + s; return s; }
function uniq_(arr) {
  var seen = {}, out = [];
  arr.forEach(function (a) { var k = String(a); if (k !== '' && !seen[k]) { seen[k] = true; out.push(a); } });
  return out;
}
function tz_() {
  try { return ss_().getSpreadsheetTimeZone() || Session.getScriptTimeZone(); } catch (e) { return 'GMT'; }
}
function today_() { var d = new Date(); d.setHours(0, 0, 0, 0); return d; }

function getUser_() {
  var e = '';
  try { e = Session.getActiveUser().getEmail(); } catch (x) { }
  if (!e) { try { e = Session.getEffectiveUser().getEmail(); } catch (x2) { } }
  return e || 'Unknown user';
}

/** Returns a Date, null for blank, or throws UmsError for invalid input. */
function toDate_(v, label) {
  if (blank_(v)) return null;
  if (v instanceof Date) {
    if (isNaN(v.getTime())) throw new UmsError('"' + label + '" is not a valid date.');
    return v;
  }
  var s = String(v).trim();
  var m = s.match(/^(\d{4})[-\/.](\d{1,2})[-\/.](\d{1,2})$/);
  var d = null;
  if (m) d = new Date(+m[1], +m[2] - 1, +m[3]);
  else {
    m = s.match(/^(\d{1,2})[-\/.](\d{1,2})[-\/.](\d{4})$/);           // dd/mm/yyyy
    if (m) d = new Date(+m[3], +m[2] - 1, +m[1]);
    else { var p = Date.parse(s); if (!isNaN(p)) d = new Date(p); }
  }
  if (!d || isNaN(d.getTime())) throw new UmsError('"' + label + '" must be a valid date (e.g. 2027-03-15). You entered: ' + s);
  return d;
}

/** Returns a number, '' for blank, or throws for invalid input. */
function toNum_(v, label, min, max) {
  if (blank_(v)) return '';
  var n = (typeof v === 'number') ? v : Number(String(v).replace(/[, ]/g, ''));
  if (isNaN(n)) throw new UmsError('"' + label + '" must be a number. You entered: ' + v);
  if (min !== undefined && n < min) throw new UmsError('"' + label + '" cannot be lower than ' + min + '.');
  if (max !== undefined && n > max) throw new UmsError('"' + label + '" cannot be higher than ' + max + '.');
  return n;
}

/** Validates a language-test score against the known range for that test. */
function validateScore_(test, score, label) {
  if (blank_(score)) return '';
  var range = SCORE_RANGES[test];
  if (!range) return (typeof score === 'number') ? score : str_(score);     // OET, SELT, Other: free text allowed
  var n = toNum_(score, label || (test + ' score'));
  if (n < range[0] || n > range[1]) throw new UmsError((label || test + ' score') + ' must be between ' + range[0] + ' and ' + range[1] + ' for ' + test + '. You entered: ' + score);
  if (test === 'IELTS' && Math.round(n * 2) !== n * 2) throw new UmsError('IELTS scores go in steps of 0.5 (e.g. 6.0, 6.5). You entered: ' + score);
  return n;
}

function fmtDate_(d) {
  if (!(d instanceof Date) || isNaN(d.getTime())) return str_(d);
  return Utilities.formatDate(d, tz_(), 'dd MMM yyyy');
}
function fmtNum_(n) {
  if (blank_(n)) return '';
  if (typeof n !== 'number') { var x = Number(String(n).replace(/,/g, '')); if (isNaN(x)) return String(n); n = x; }
  var parts = (Math.round(n * 100) / 100).toString().split('.');
  parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ',');
  return parts.join('.');
}
/** GPA / IELTS style numbers: 3 → "3.0", 3.25 → "3.25" (other values unchanged). */
function fmtDec_(v) {
  if (blank_(v)) return '';
  var n = Number(v);
  if (isNaN(n)) return String(v);
  return (Math.round(n) === n) ? n.toFixed(1) : String(n);
}
function fmtScore_(test, v) {
  if (blank_(v)) return '';
  return (test === 'IELTS' || test === 'GPA') ? fmtDec_(v) : String(v);
}
function fmtMoney_(amount, currency, period) {
  if (blank_(amount)) return '';
  var s = fmtNum_(amount) + (currency ? ' ' + currency : '');
  if (period) s += ' / ' + String(period).replace(/^Per /, '').toLowerCase();
  return s;
}
function daysUntil_(d) {
  if (!(d instanceof Date)) return null;
  var x = new Date(d.getTime()); x.setHours(0, 0, 0, 0);
  return Math.round((x.getTime() - today_().getTime()) / 86400000);
}

/* ---------------------------------------------------------------------------
 *  MESSAGES
 * ------------------------------------------------------------------------- */
function notify_(message, title) {
  try {
    var ui = SpreadsheetApp.getUi();
    ui.alert(title || 'University Management System', message, ui.ButtonSet.OK);
  } catch (e) {
    try { ss_().toast(message, title || 'University Management System', 8); } catch (e2) { Logger.log(message); }
  }
}
function toast_(message, title, secs) {
  try { ss_().toast(message, title || 'University System', secs || 5); } catch (e) { Logger.log(message); }
}
function confirm_(message, title) {
  try {
    var ui = SpreadsheetApp.getUi();
    return ui.alert(title || 'Please confirm', message, ui.ButtonSet.YES_NO) === ui.Button.YES;
  } catch (e) { return true; }
}

/**
 * Wraps every user action: document lock (prevents double submissions),
 * friendly errors, and error logging to the Audit Log.
 */
function run_(actionName, fn, opts) {
  opts = opts || {};
  var lock = null;
  if (opts.lock !== false) {
    lock = LockService.getDocumentLock();
    if (!lock.tryLock(30000)) {
      notify_('Another user is saving right now. Please wait a few seconds and press the button again.', 'System busy');
      return null;
    }
  }
  try {
    _CACHE = {}; _SETTINGS = null;
    return fn();
  } catch (e) {
    if (e && e.name === 'UmsError') {
      notify_(e.message, opts.title || '⚠ ' + actionName);
    } else {
      logError_(actionName, e);
      notify_('Something went wrong in "' + actionName + '":\n\n' + (e && e.message ? e.message : e) +
        '\n\nThe error has been recorded in 15_Audit_Log.', 'Error');
    }
    return null;
  } finally {
    if (lock) { try { lock.releaseLock(); } catch (x) { } }
  }
}

/* ---------------------------------------------------------------------------
 *  AUDIT LOG
 * ------------------------------------------------------------------------- */
function writeAuditLog(action, sheetName, recordId, country, university, program, oldValue, newValue) {
  try {
    var sh = ss_().getSheetByName(UMS.SHEETS.AUDIT);
    if (!sh) return;
    var row = [new Date(), getUser_(), action || '', sheetName || '', recordId || '', country || '', university || '',
      program || '', trimLog_(oldValue), trimLog_(newValue)];
    var target = Math.max(sh.getLastRow(), 1) + 1;
    ensureCapacity_(sh, target);
    sh.getRange(target, 1, 1, row.length).setValues([row]);
  } catch (e) { Logger.log('Audit log failed: ' + e); }
}
function trimLog_(v) {
  if (v === null || v === undefined) return '';
  if (typeof v === 'object' && !(v instanceof Date)) {
    var parts = [];
    for (var k in v) if (k.charAt(0) !== '_') parts.push(k + ': ' + (v[k] instanceof Date ? fmtDate_(v[k]) : v[k]));
    v = parts.join(' | ');
  }
  v = String(v);
  return v.length > 45000 ? v.substring(0, 45000) + '…' : v;
}
function logError_(where, e) {
  writeAuditLog('ERROR', '', '', '', '', '', where, (e && e.message ? e.message : String(e)) + (e && e.stack ? ' | ' + e.stack : ''));
}
function auditChanges_(action, sheetName, rec, changed) {
  if (!changed || !Object.keys(changed).length) return;
  var o = [], n = [];
  for (var k in changed) {
    var h = schemaHeader_(sheetName, k) || k;
    o.push(h + ': ' + (changed[k][0] instanceof Date ? fmtDate_(changed[k][0]) : changed[k][0]));
    n.push(h + ': ' + (changed[k][1] instanceof Date ? fmtDate_(changed[k][1]) : changed[k][1]));
  }
  writeAuditLog(action, sheetName, rec.id, rec.country, rec.university || rec.name, rec.program || '', o.join(' | '), n.join(' | '));
}

/* ---------------------------------------------------------------------------
 *  ID GENERATION     KR · KR-UNI-001 · KR-UNI-001-C01 · KR-UNI-001-P001
 *                    KR-UNI-001-INT001 · …-INT001-R01 · KR-UNI-001-SCH001 …
 * ------------------------------------------------------------------------- */
function nextSeq_(table, prefix, width) {
  var max = 0, re = new RegExp('^' + prefix.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + '(\\d+)$');
  readTable_(table).objs.forEach(function (o) {
    var m = String(o.id || '').match(re);
    if (m) max = Math.max(max, parseInt(m[1], 10));
  });
  return prefix + pad_(max + 1, width);
}

/**
 * generateID(type, parentId|countryName)
 *   type: country | university | campus | program | intake | round | academic |
 *         language | fee | scholarship | tier | document | visa | source | csr
 */
function generateID(type, parent) {
  switch (type) {
    case 'country': {
      var name = norm_(parent);
      var code = COUNTRY_CODES[name] || name.replace(/[^a-z]/g, '').substring(0, 2).toUpperCase() || 'XX';
      var used = {};
      readTable_(UMS.SHEETS.COUNTRIES).objs.forEach(function (c) { used[String(c.id)] = norm_(c.country); });
      if (!used[code] || used[code] === name) return code;
      var letters = name.replace(/[^a-z]/g, '').toUpperCase();
      for (var i = 1; i < letters.length; i++) { var c2 = letters.charAt(0) + letters.charAt(i); if (!used[c2]) return c2; }
      for (var n = 1; n < 100; n++) { var c3 = code.charAt(0) + n; if (!used[c3]) return c3; }
      return code + '9';
    }
    case 'university': return nextSeq_(UMS.SHEETS.UNIVERSITIES, parent + '-UNI-', 3);
    case 'campus': return nextSeq_(UMS.SHEETS.CAMPUSES, parent + '-C', 2);
    case 'program': return nextSeq_(UMS.SHEETS.PROGRAMS, parent + '-P', 3);
    case 'intake': return nextSeq_(UMS.SHEETS.INTAKES, parent + '-INT', 3);
    case 'round': return nextSeq_(UMS.SHEETS.ROUNDS, parent + '-R', 2);
    case 'academic': return nextSeq_(UMS.SHEETS.ACADEMIC, parent + '-AR', 3);
    case 'language': return nextSeq_(UMS.SHEETS.LANGUAGE, parent + '-LR', 3);
    case 'fee': return nextSeq_(UMS.SHEETS.FEES, parent + '-FEE', 3);
    case 'scholarship': return nextSeq_(UMS.SHEETS.SCHOLARSHIPS, parent + '-SCH', 3);
    case 'tier': return nextSeq_(UMS.SHEETS.TIERS, parent + '-T', 2);
    case 'document': return nextSeq_(UMS.SHEETS.DOCUMENTS, parent + '-DOC', 3);
    case 'visa': return nextSeq_(UMS.SHEETS.VISA, parent + '-VISA', 2);
    case 'source': return nextSeq_(UMS.SHEETS.SOURCES, parent + '-SRC', 2);
    case 'csr': return nextSeq_(UMS.SHEETS.CSR, parent + '-CSR', 3);
  }
  throw new Error('Unknown ID type: ' + type);
}

/* ---------------------------------------------------------------------------
 *  DUPLICATE DETECTION
 *   University  : Country + University
 *   Program     : University + Degree + Program
 *   Intake      : University + Intake (+ Round for rounds)
 *   Scholarship : University + Scholarship + Program
 *  Returns the existing record or null. excludeId lets updates ignore self.
 * ------------------------------------------------------------------------- */
function checkDuplicate(type, rec, excludeId) {
  var S = UMS.SHEETS, t, key;
  var not = function (o) { return !excludeId || String(o.id) !== String(excludeId); };
  switch (type) {
    case 'country':
      t = readTable_(S.COUNTRIES).objs;
      return first_(t, function (o) { return not(o) && (norm_(o.country) === norm_(rec.country) || (rec.id && String(o.id) === String(rec.id))); });
    case 'university':
      t = readTable_(S.UNIVERSITIES).objs;
      return first_(t, function (o) { return not(o) && String(o.countryId) === String(rec.countryId) && norm_(o.name) === norm_(rec.name); });
    case 'campus':
      t = readTable_(S.CAMPUSES).objs;
      return first_(t, function (o) { return not(o) && o.uniId === rec.uniId && norm_(o.campus) === norm_(rec.campus); });
    case 'program':
      t = readTable_(S.PROGRAMS).objs;
      return first_(t, function (o) {
        return not(o) && o.uniId === rec.uniId && norm_(o.degree) === norm_(rec.degree) && norm_(o.program) === norm_(rec.program) &&
          (blank_(rec.campus) || blank_(o.campus) || norm_(o.campus) === norm_(rec.campus));
      });
    case 'intake':
      t = readTable_(S.INTAKES).objs;
      return first_(t, function (o) { return not(o) && o.uniId === rec.uniId && norm_(o.intake) === norm_(rec.intake) && norm_(o.degree || 'All') === norm_(rec.degree || 'All'); });
    case 'round':
      t = readTable_(S.ROUNDS).objs;
      return first_(t, function (o) { return not(o) && o.intakeId === rec.intakeId && norm_(o.round) === norm_(rec.round); });
    case 'academic':
      t = readTable_(S.ACADEMIC).objs;
      return first_(t, function (o) { return not(o) && o.uniId === rec.uniId && String(o.programId) === String(rec.programId) && norm_(o.degree) === norm_(rec.degree); });
    case 'language':
      t = readTable_(S.LANGUAGE).objs;
      return first_(t, function (o) { return not(o) && o.uniId === rec.uniId && String(o.programId) === String(rec.programId) && norm_(o.degree) === norm_(rec.degree) && norm_(o.test) === norm_(rec.test); });
    case 'fee':
      t = readTable_(S.FEES).objs;
      return first_(t, function (o) { return not(o) && o.uniId === rec.uniId && String(o.programId) === String(rec.programId) && norm_(o.degree) === norm_(rec.degree) && norm_(o.feeType) === norm_(rec.feeType) && norm_(o.notes) === norm_(rec.notes); });
    case 'scholarship':
      t = readTable_(S.SCHOLARSHIPS).objs;
      return first_(t, function (o) { return not(o) && o.uniId === rec.uniId && norm_(o.name) === norm_(rec.name) && String(o.programId) === String(rec.programId); });
    case 'tier':
      t = readTable_(S.TIERS).objs;
      return first_(t, function (o) { return not(o) && o.schId === rec.schId && norm_(o.tier) === norm_(rec.tier); });
    case 'document':
      t = readTable_(S.DOCUMENTS).objs;
      return first_(t, function (o) { return not(o) && o.uniId === rec.uniId && String(o.programId) === String(rec.programId) && norm_(o.degree) === norm_(rec.degree) && norm_(o.document) === norm_(rec.document); });
    case 'csr':
      t = readTable_(S.CSR).objs;
      return first_(t, function (o) { return not(o) && o.countryId === rec.countryId && String(o.uniId) === String(rec.uniId) && norm_(o.category) === norm_(rec.category) && norm_(o.requirement) === norm_(rec.requirement); });
    case 'visa':
      t = readTable_(S.VISA).objs;
      return first_(t, function (o) { return not(o) && o.countryId === rec.countryId && norm_(o.visaType) === norm_(rec.visaType); });
    case 'source':
      t = readTable_(S.SOURCES).objs;
      return first_(t, function (o) { return not(o) && o.uniId === rec.uniId && norm_(o.url) === norm_(rec.url); });
  }
  return null;
}
function first_(arr, fn) { for (var i = 0; i < arr.length; i++) if (fn(arr[i])) return arr[i]; return null; }

/* ---------------------------------------------------------------------------
 *  LOOKUPS
 * ------------------------------------------------------------------------- */
function countryByName_(name) {
  if (blank_(name)) return null;
  var n = norm_(name);
  return first_(readTable_(UMS.SHEETS.COUNTRIES).objs, function (c) { return norm_(c.country) === n || norm_(c.id) === n; });
}
function universityByName_(name, countryName) {
  if (blank_(name)) return null;
  var n = norm_(name), c = countryName ? norm_(countryName) : null;
  return first_(readTable_(UMS.SHEETS.UNIVERSITIES).objs, function (u) {
    return norm_(u.name) === n && (!c || norm_(u.country) === c);
  });
}
function programLabel_(p) { return str_(p.program) + ' (' + str_(p.degree) + ')'; }
function programByLabel_(uniId, label) {
  if (blank_(label)) return null;
  var n = norm_(label);
  return first_(readTable_(UMS.SHEETS.PROGRAMS).objs, function (p) {
    return p.uniId === uniId && (norm_(programLabel_(p)) === n || norm_(p.program) === n);
  });
}
function intakeByName_(uniId, name) {
  if (blank_(name)) return null;
  var n = norm_(name);
  return first_(readTable_(UMS.SHEETS.INTAKES).objs, function (i) { return i.uniId === uniId && (norm_(i.intake) === n || norm_(i.id) === n); });
}
function isActive_(o) { return String(o.status) !== 'Archived'; }

/** Records that apply to a program: program-specific rows + university-wide ("ALL") rows of the same degree. */
function appliesTo_(rec, program) {
  if (String(rec.programId) === String(program.id)) return true;
  if (str_(rec.programId).toUpperCase() === 'ALL' || blank_(rec.programId)) {
    return blank_(rec.degree) || rec.degree === 'All' || rec.degree === program.degree;
  }
  return false;
}


/* ============================================================================
 *  CANVAS RENDERER
 *  Dashboards are drawn into an in-memory grid, then written to the sheet with
 *  a handful of batched calls (values, colours, alignment, merges, borders).
 *  Canvas coordinates use CONTENT columns 1..n; the renderer adds a gutter
 *  column on the left and right automatically.
 * ========================================================================== */

var GRID = {
  wide: { cols: 12, colW: 105, gutter: 18 },
  print: { cols: 10, colW: 72, gutter: 14 }
};

function Canvas(mode) {
  this.mode = mode || 'wide';
  this.n = GRID[this.mode].cols;
  this.W = this.n + 2;
  this.cells = []; this.heights = [];
  this.merges = []; this.borders = []; this.formulas = []; this.raws = [];
  this.validations = []; this.names = {}; this.buttons = []; this.notes = [];
  this.checkboxes = [];
  this.base = 0;            // sheet row offset when flushed below other content
}
/** A1 address of canvas (row, content col), honouring the flush offset. */
Canvas.prototype.a1 = function (r, c) { return colLetter_(c + 1) + (r + 1 + this.base); };

Canvas.prototype.blankCell_ = function (bg) {
  return { t: '', bg: bg || UMS.C.PAGE, fc: UMS.C.TEXT, b: false, i: false, u: false, fs: 10, ha: 'left', va: 'middle', wrap: false, link: null };
};
/** Adds a row. Returns its canvas index. */
Canvas.prototype.row = function (h, bg) {
  var r = [];
  for (var c = 0; c < this.W; c++) r.push(this.blankCell_(bg));
  this.cells.push(r); this.heights.push(h || 21);
  return this.cells.length - 1;
};
Canvas.prototype.spacer = function (h) { return this.row(h || 10); };
Canvas.prototype.fill = function (r, c1, c2, bg) {
  for (var c = c1; c <= c2; c++) this.cells[r][c].bg = bg;
};
/** Full-width fill including gutters. */
Canvas.prototype.band = function (r, bg) { for (var c = 0; c < this.W; c++) this.cells[r][c].bg = bg; };
/**
 * Writes text at content column c spanning `span` columns (merged).
 * style: {bg, fc, b, i, u, fs, ha, va, wrap, link, rs (row span)}
 */
Canvas.prototype.put = function (r, c, span, text, style) {
  style = style || {};
  span = span || 1;
  var rs = style.rs || 1;
  for (var rr = r; rr < r + rs; rr++) {
    for (var cc = c; cc < c + span; cc++) {
      var cell = this.cells[rr][cc];
      if (style.bg !== undefined) cell.bg = style.bg;
    }
  }
  var cell0 = this.cells[r][c];
  cell0.t = (text === null || text === undefined) ? '' : String(text);
  ['fc', 'b', 'i', 'u', 'fs', 'ha', 'va', 'wrap', 'link'].forEach(function (k) { if (style[k] !== undefined) cell0[k] = style[k]; });
  if (span > 1 || rs > 1) this.merges.push([r, c, rs, span]);
  return this;
};
Canvas.prototype.box = function (r1, c1, r2, c2, color, style, inner) {
  this.borders.push({ r1: r1, c1: c1, r2: r2, c2: c2, color: color || UMS.C.LINE, style: style || 'SOLID', inner: inner || null });
  return this;
};
Canvas.prototype.line = function (r, c1, c2, side, color, style) {
  this.borders.push({ r1: r, c1: c1, r2: r, c2: c2, color: color || UMS.C.LINE, style: style || 'SOLID', side: side || 'bottom' });
  return this;
};
Canvas.prototype.formula = function (r, c, f, nf) { this.formulas.push([r, c, f, nf || null]); return this; };
Canvas.prototype.raw = function (r, c, v, nf) { this.raws.push([r, c, v, nf || null]); return this; };
Canvas.prototype.validate = function (r, c, spec) { this.validations.push([r, c, spec]); return this; };
Canvas.prototype.name = function (key, r, c) { this.names[key] = [r, c]; return this; };
Canvas.prototype.button = function (r, c, span, fn, rs) { this.buttons.push([r, c, span, rs || 1, fn]); return this; };
Canvas.prototype.rowCount = function () { return this.cells.length; };

/* --- Pixel helpers (used to size transparent click-overlays) ------------- */
Canvas.prototype.colX = function (c) {                // left offset of content col c inside the sheet
  var g = GRID[this.mode];
  return g.gutter + (c - 1) * g.colW;
};
Canvas.prototype.spanW = function (span) { return GRID[this.mode].colW * span; };
Canvas.prototype.rowsH = function (r, rs) { var h = 0; for (var i = r; i < r + rs; i++) h += this.heights[i]; return h; };

/* ---------------------------------------------------------------------------
 *  FLUSH  — writes the canvas to `sheet` starting at `startRow`
 * ------------------------------------------------------------------------- */
var _STYLE_CACHE = {};
function textStyle_(cell) {
  var key = [cell.fc, cell.b, cell.i, cell.u, cell.fs].join('|');
  if (_STYLE_CACHE[key]) return _STYLE_CACHE[key];
  var st = SpreadsheetApp.newTextStyle().setFontFamily(UMS.FONT).setForegroundColor(cell.fc).setBold(!!cell.b)
    .setItalic(!!cell.i).setUnderline(!!cell.u).setFontSize(cell.fs).build();
  _STYLE_CACHE[key] = st;
  return st;
}

Canvas.prototype.flush = function (sheet, startRow) {
  startRow = startRow || 1;
  var nR = this.cells.length, nC = this.W;
  if (!nR) return;
  ensureCapacity_(sheet, startRow + nR - 1);
  if (sheet.getMaxColumns() < nC) sheet.insertColumnsAfter(sheet.getMaxColumns(), nC - sheet.getMaxColumns());
  var rng = sheet.getRange(startRow, 1, nR, nC);
  var S = this;
  var bgs = [], fcs = [], fws = [], fss = [], has = [], vas = [], wraps = [], rts = [], fams = [];
  for (var r = 0; r < nR; r++) {
    var b = [], f = [], w = [], s = [], h = [], v = [], wr = [], rt = [], fm = [];
    for (var c = 0; c < nC; c++) {
      var cell = this.cells[r][c];
      b.push(cell.bg); f.push(cell.fc); w.push(cell.b ? 'bold' : 'normal'); s.push(cell.fs);
      h.push(cell.ha); v.push(cell.va); wr.push(!!cell.wrap); fm.push(UMS.FONT);
      var builder = SpreadsheetApp.newRichTextValue().setText(cell.t);
      if (cell.t !== '') {
        builder = builder.setTextStyle(textStyle_(cell));
        if (cell.link) builder = builder.setLinkUrl(cell.link);
      }
      rt.push(builder.build());
    }
    bgs.push(b); fcs.push(f); fws.push(w); fss.push(s); has.push(h); vas.push(v); wraps.push(wr); rts.push(rt); fams.push(fm);
  }
  rng.setFontFamilies(fams);
  rng.setFontColors(fcs);
  rng.setFontWeights(fws);
  rng.setFontSizes(fss);
  rng.setRichTextValues(rts);
  rng.setBackgrounds(bgs);
  rng.setHorizontalAlignments(has);
  rng.setVerticalAlignments(vas);
  rng.setWraps(wraps);

  // Merges: single-row merges with the same column/span on consecutive rows are
  // done with ONE mergeAcross() call (much faster than one call per cell).
  var single = {}, multi = [];
  this.merges.forEach(function (m) {
    if (m[2] === 1) { var k = m[1] + '|' + m[3]; (single[k] = single[k] || []).push(m[0]); }
    else multi.push(m);
  });
  Object.keys(single).forEach(function (k) {
    var parts = k.split('|'), c = +parts[0], span = +parts[1];
    var rs = single[k].sort(function (a, b) { return a - b; });
    var i2 = 0;
    while (i2 < rs.length) {
      var j2 = i2;
      while (j2 + 1 < rs.length && rs[j2 + 1] === rs[j2] + 1) j2++;
      var block = sheet.getRange(startRow + rs[i2], c + 1, j2 - i2 + 1, span);
      if (j2 === i2) block.merge(); else block.mergeAcross();
      i2 = j2 + 1;
    }
  });
  multi.forEach(function (m) {
    sheet.getRange(startRow + m[0], m[1] + 1, m[2], m[3]).merge();
  });

  var BS = SpreadsheetApp.BorderStyle;
  this.borders.forEach(function (bd) {
    var st = BS[bd.style] || BS.SOLID;
    var rg = sheet.getRange(startRow + bd.r1, bd.c1 + 1, bd.r2 - bd.r1 + 1, bd.c2 - bd.c1 + 1);
    if (bd.side) {
      rg.setBorder(bd.side === 'top' ? true : null, bd.side === 'left' ? true : null, bd.side === 'bottom' ? true : null,
        bd.side === 'right' ? true : null, null, null, bd.color, st);
    } else {
      rg.setBorder(true, true, true, true, null, null, bd.color, st);
      if (bd.inner) rg.setBorder(null, null, null, null, bd.inner.v ? true : null, bd.inner.h ? true : null, bd.inner.color || UMS.C.LINE_L, BS.SOLID);
    }
  });

  // row heights — grouped for speed
  var i = 0;
  while (i < nR) {
    var j = i;
    while (j + 1 < nR && this.heights[j + 1] === this.heights[i]) j++;
    sheet.setRowHeights(startRow + i, j - i + 1, this.heights[i]);
    i = j + 1;
  }

  this.raws.forEach(function (x) {
    var cl = sheet.getRange(startRow + x[0], x[1] + 1);
    cl.setValue(x[2]);
    if (x[3]) cl.setNumberFormat(x[3]);
  });
  this.formulas.forEach(function (x) {
    var cl = sheet.getRange(startRow + x[0], x[1] + 1);
    cl.setFormula(x[2]);
    if (x[3]) cl.setNumberFormat(x[3]);
  });
  this.validations.forEach(function (x) {
    var cl = sheet.getRange(startRow + x[0], x[1] + 1);
    var rule = buildValidation_(x[2]);
    if (rule) cl.setDataValidation(rule);
    if (x[2].nf) cl.setNumberFormat(x[2].nf);
  });
  this.checkboxes.forEach(function (x) { sheet.getRange(startRow + x[0], x[1] + 1).insertCheckboxes(); });
};

/** Clears everything from `startRow` downwards (values, formats, merges, validation). */
function clearRegion_(sheet, startRow) {
  var maxR = sheet.getMaxRows(), maxC = sheet.getMaxColumns();
  if (startRow > maxR) return;
  var rg = sheet.getRange(startRow, 1, maxR - startRow + 1, maxC);
  rg.breakApart();
  rg.clearDataValidations();
  rg.clear();
}

/** After rendering, keep a tidy page: trim far-away empty rows and paint padding. */
function finishPage_(sheet, lastUsedRow, mode) {
  var pad = 25, want = lastUsedRow + pad, max = sheet.getMaxRows();
  if (max > want + 5) sheet.deleteRows(want + 1, max - want);
  else if (max < want) sheet.insertRowsAfter(max, want - max);
  var W = GRID[mode || 'wide'].cols + 2;
  var rg = sheet.getRange(lastUsedRow + 1, 1, pad, W);
  rg.setBackground(UMS.C.PAGE);
  sheet.setRowHeights(lastUsedRow + 1, pad, 21);
}

/* ---------------------------------------------------------------------------
 *  DATA VALIDATION BUILDER
 *   {list:'KEY'} settings list · {helper:'COL'} dependent list in _Lists
 *   {values:[..]} · {date:true} · {number:[min,max]} · {url:true} · {loose:true}
 * ------------------------------------------------------------------------- */
function buildValidation_(spec) {
  var b = SpreadsheetApp.newDataValidation();
  if (spec.list) {
    b = b.requireValueInRange(ss_().getRange(listRangeA1_(spec.list)), true);
  } else if (spec.helper) {
    b = b.requireValueInRange(ss_().getRange(helperRangeA1_(spec.helper)), true);
  } else if (spec.values) {
    b = b.requireValueInList(spec.values, true);
  } else if (spec.date) {
    b = b.requireDate();
  } else if (spec.number) {
    b = b.requireNumberBetween(spec.number[0], spec.number[1]);
  } else if (spec.url) {
    b = b.requireTextIsUrl();
  } else return null;
  b = b.setAllowInvalid(spec.loose === true || !!spec.url || !!spec.number);
  if (spec.help) b = b.setHelpText(spec.help);
  return b.build();
}

/* ---------------------------------------------------------------------------
 *  SHARED DESIGN PIECES
 * ------------------------------------------------------------------------- */
var NAV = [
  // label, sheet, handler, wide span, print span, print label
  ['⌂  HOME', 'HOME', 'navHome', 1, 1, 'HOME'],
  ['🔍  UNIVERSITY SEARCH', 'University Search', 'navUniversitySearch', 2, 2, 'UNI SEARCH'],
  ['📚  PROGRAM SEARCH', 'Program Search', 'navProgramSearch', 2, 2, 'PROGRAMS'],
  ['✎  DATA ENTRY', 'University Data Entry', 'navDataEntry', 2, 2, 'DATA ENTRY'],
  ['🖨  PRINT VIEW', 'Print View', 'navPrintView', 2, 1, 'PRINT'],
  ['🗄  DATABASE', '03_Universities', 'navDatabase', 2, 1, 'DATABASE'],
  ['⚙  SETTINGS', '01_Settings', 'navSettings', 1, 1, 'SETTINGS']
];

function sheetLink_(name) {
  var sh = ss_().getSheetByName(name);
  return sh ? '#gid=' + sh.getSheetId() : null;
}

/** Header band + navigation bar (6 rows). */
function drawHeader_(cv, title, subtitle, activeSheet) {
  var C = UMS.C, n = cv.n, isPrint = cv.mode === 'print';
  var r0 = cv.row(10); cv.band(r0, C.NAVY);
  var r1 = cv.row(isPrint ? 30 : 36); cv.band(r1, C.NAVY);
  var tSpan = isPrint ? 7 : 8;
  cv.put(r1, 1, tSpan, title, { fc: C.WHITE, b: true, fs: isPrint ? 15 : 19, va: 'bottom' });
  cv.put(r1, tSpan + 1, n - tSpan, str_(setting_('Consultancy Name')), { fc: C.WHITE, b: true, fs: 10, ha: 'right', va: 'bottom' });
  var r2 = cv.row(22); cv.band(r2, C.NAVY);
  cv.put(r2, 1, tSpan, subtitle, { fc: C.SUB, fs: 10, va: 'top' });
  cv.put(r2, tSpan + 1, n - tSpan, '', { fc: C.SUB, fs: 9, ha: 'right', va: 'top' });
  cv.formula(r2, tSpan + 1, '=TEXT(TODAY(),"dddd, dd mmm yyyy")');
  var r3 = cv.row(8); cv.band(r3, C.NAVY);
  var r4 = cv.row(isPrint ? 28 : 32); cv.band(r4, C.NAVY2);
  var c = 1;
  NAV.forEach(function (nv) {
    var span = isPrint ? nv[4] : nv[3];
    var active = nv[1] === activeSheet;
    cv.put(r4, c, span, isPrint ? nv[5] : nv[0], {
      bg: active ? C.BLUE : C.NAVY2, fc: active ? C.WHITE : C.SUB, b: true, fs: isPrint ? 8 : 9, ha: 'center'
    });
    cv.button(r4, c, span, nv[2]);
    c += span;
  });
  cv.line(r4, 1, n, 'bottom', C.BLUE, 'SOLID_MEDIUM');
  cv.spacer(12);
}

var BTN = {
  primary: { bg: UMS.C.BLUE, fc: UMS.C.WHITE },
  success: { bg: UMS.C.TEAL, fc: UMS.C.WHITE },
  accent: { bg: UMS.C.AMBER, fc: UMS.C.WHITE },
  purple: { bg: UMS.C.PURPLE, fc: UMS.C.WHITE },
  light: { bg: UMS.C.WHITE, fc: UMS.C.NAVY },
  danger: { bg: UMS.C.ROSE_L, fc: UMS.C.ROSE }
};
function drawButton_(cv, r, c, span, label, kind, fn) {
  var k = BTN[kind] || BTN.primary;
  cv.put(r, c, span, label, { bg: k.bg, fc: k.fc, b: true, fs: 10, ha: 'center' });
  cv.box(r, c, r, c + span - 1, kind === 'light' || kind === 'danger' ? k.fc : k.bg, 'SOLID_MEDIUM');
  if (fn) cv.button(r, c, span, fn);
}

/** Card title row: coloured accent strip on the left, light background. */
function drawSectionTitle_(cv, text, color, right) {
  var C = UMS.C, n = cv.n;
  var r = cv.row(28);
  cv.fill(r, 1, n, '#E8EEF7');
  cv.put(r, 1, n - (right ? 4 : 0), text, { bg: '#E8EEF7', fc: C.NAVY, b: true, fs: 11 });
  if (right) cv.put(r, n - 3, 4, right, { bg: '#E8EEF7', fc: C.MUTED, fs: 9, ha: 'right', i: true });
  cv.line(r, 1, 1, 'left', color || C.BLUE, 'SOLID_THICK');
  cv.line(r, 1, n, 'bottom', color || C.BLUE, 'SOLID_MEDIUM');
  return r;
}

/**
 * Generic table.  cols: [{h:'Header', span:2, align:'center'}]
 * rows: array of arrays of cell specs (string | {t, bg, fc, b, link, ha, badge})
 */
function drawTable_(cv, cols, rows, opts) {
  opts = opts || {};
  var C = UMS.C;
  var hr = cv.row(24);
  var c = 1;
  cols.forEach(function (col) {
    cv.put(hr, c, col.span, col.h, { bg: C.HEAD_BG, fc: C.LABEL, b: true, fs: 9, ha: col.align || 'left', wrap: true });
    c += col.span;
  });
  var start = hr;
  if (!rows.length) {
    var er = cv.row(26);
    cv.put(er, 1, cv.n, opts.empty || 'No records yet.', { bg: C.CARD, fc: C.MUTED, i: true, fs: 9, ha: 'center' });
    cv.box(start, 1, er, cv.n, C.LINE, 'SOLID');
    return { header: hr, first: er, last: er };
  }
  var first = null, last = null;
  rows.forEach(function (row, ri) {
    var h = opts.rowHeight || 24;
    var maxLen = 0;
    row.forEach(function (x, ci) {
      var t = (x && typeof x === 'object') ? str_(x.t) : str_(x);
      var per = Math.max(8, Math.floor(cols[ci].span * 13));
      maxLen = Math.max(maxLen, Math.ceil(t.length / per));
    });
    if (maxLen > 1) h = Math.min(90, 16 + maxLen * 14);
    var r = cv.row(h);
    if (first === null) first = r;
    last = r;
    var bg = (ri % 2 === 1) ? C.ZEBRA : C.CARD;
    var cc = 1;
    row.forEach(function (x, ci) {
      var spec = (x && typeof x === 'object') ? x : { t: x };
      var style = { bg: spec.bg || bg, fc: spec.fc || C.TEXT, b: !!spec.b, fs: spec.fs || 9, ha: spec.ha || cols[ci].align || 'left', wrap: true, link: spec.link || null, u: !!spec.link, i: !!spec.i };
      if (spec.link) style.fc = spec.fc || C.BLUE;
      cv.put(r, cc, cols[ci].span, spec.t, style);
      cc += cols[ci].span;
    });
  });
  cv.box(start, 1, last, cv.n, C.LINE, 'SOLID', { h: true, color: C.LINE_L });
  cv.line(hr, 1, cv.n, 'bottom', C.LINE, 'SOLID');
  return { header: hr, first: first, last: last };
}

function statusCell_(status) {
  var s = UMS.STATUS_STYLE[str_(status)];
  if (!s) return { t: str_(status) || '—', fc: UMS.C.MUTED, ha: 'center' };
  return { t: '● ' + status, bg: s.bg, fc: s.fc, b: true, ha: 'center' };
}
function deadlineStyle_(d) {
  var days = daysUntil_(d);
  if (days === null) return null;
  var orange = Number(setting_('Deadline Warning Days (Orange)')) || 7;
  var yellow = Number(setting_('Deadline Notice Days (Yellow)')) || 30;
  if (days < 0) return { bg: '#FEE2E2', fc: '#991B1B', label: 'Passed', days: days };
  if (days <= orange) return { bg: '#FFEDD5', fc: '#C2410C', label: days === 0 ? 'Today' : days + ' days', days: days };
  if (days <= yellow) return { bg: '#FEF9C3', fc: '#854D0E', label: days + ' days', days: days };
  return { bg: null, fc: UMS.C.TEXT, label: days + ' days', days: days };
}
function sampleBadge_(o) { return String(o.dataFlag || '').indexOf('SAMPLE') >= 0; }

/* ---------------------------------------------------------------------------
 *  CLICK OVERLAYS  (transparent images with an assigned script = real buttons)
 * ------------------------------------------------------------------------- */
var TRANSPARENT_PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAQAAAAECAYAAACp8Z5+AAAAFElEQVR42mP8//8/AzJgYkADhAUAxo4DBb0+8e8AAAAASUVORK5CYII=';

function placeButtons_(sheet, cv, startRow) {
  if (!cv.buttons.length) return;
  var bytes = Utilities.base64Decode(TRANSPARENT_PNG);
  // pixel offset of the first canvas row = sum of row heights above startRow
  var above = 0;
  for (var r = 1; r < startRow; r++) above += sheet.getRowHeight(r);
  cv.buttons.forEach(function (b) {
    try {
      var blob = Utilities.newBlob(bytes, 'image/png', 'btn.png');
      var row = startRow + b[0];
      var img = sheet.insertImage(blob, b[1] + 1, row, 0, 0);
      img.setWidth(cv.spanW(b[2])).setHeight(Math.max(10, cv.rowsH(b[0], b[3]) - 1));
      img.assignScript(b[4]);
      img.setAltTextTitle(UMS.BUTTON_TAG);
      img.setAltTextDescription(b[4]);
    } catch (e) { Logger.log('Button overlay failed: ' + e); }
  });
}
function removeButtons_(sheet, fromRow) {
  try {
    sheet.getImages().forEach(function (img) {
      if (img.getAltTextTitle() === UMS.BUTTON_TAG) {
        if (!fromRow || img.getAnchorCell().getRow() >= fromRow) img.remove();
      }
    });
  } catch (e) { Logger.log('Remove buttons failed: ' + e); }
}

/** Standard column widths for a dashboard sheet. */
function sizeColumns_(sheet, mode) {
  var g = GRID[mode];
  var W = g.cols + 2;
  var max = sheet.getMaxColumns();
  if (max > W) sheet.deleteColumns(W + 1, max - W);
  else if (max < W) sheet.insertColumnsAfter(max, W - max);
  sheet.setColumnWidth(1, g.gutter);
  sheet.setColumnWidths(2, g.cols, g.colW);
  sheet.setColumnWidth(W, g.gutter);
  sheet.setHiddenGridlines(true);
}


/* ============================================================================
 *  HELPER LISTS (_Lists sheet) — powers the dependent dropdowns
 * ========================================================================== */

var HELPER = {
  COUNTRIES: 1, US_UNIV: 2, US_CAMPUS: 3, US_INTAKE: 4, US_PROGRAM: 5,
  PS_UNIV: 6, PS_INTAKE: 7, PS_MAJOR: 8,
  DE_UNIV: 9, DE_PROGRAM: 10, DE_INTAKE: 11, DE_ROUND: 12, DE_CAMPUS: 13, DE_SCH: 14,
  PV_UNIV: 15, UNI_COUNTRY: 16
};
var HELPER_ROWS = 3000;

function helperRangeA1_(key) {
  var L = colLetter_(HELPER[key]);
  return "'" + UMS.SHEETS.LISTS + "'!$" + L + '$2:$' + L + '$' + (HELPER_ROWS + 1);
}

/** Replaces one helper column with a new list of values. */
function writeHelper_(key, values) {
  var sh = getSheet_(UMS.SHEETS.LISTS);
  var col = HELPER[key];
  var cur = sh.getLastRow();
  if (cur > 1) sh.getRange(2, col, cur - 1, 1).clearContent();
  values = uniq_(values).slice(0, HELPER_ROWS);
  if (values.length) sh.getRange(2, col, values.length, 1).setValues(values.map(function (v) { return [v]; }));
}

function activeCountries_() {
  return readTable_(UMS.SHEETS.COUNTRIES).objs
    .filter(function (c) { return String(c.active) !== 'No' && isActive_(c); })
    .map(function (c) { return str_(c.country); })
    .sort();
}
function universitiesOf_(countryName) {
  var n = norm_(countryName);
  return readTable_(UMS.SHEETS.UNIVERSITIES).objs
    .filter(function (u) { return isActive_(u) && (!n || norm_(u.country) === n); })
    .map(function (u) { return str_(u.name); }).sort();
}

/**
 * refreshDropdowns() — rebuilds every dropdown list from the database.
 * Called automatically after every add/update; also available in the menu.
 */
function refreshDropdowns() {
  var countries = activeCountries_();
  writeHelper_('COUNTRIES', countries);
  writeHelper_('UNI_COUNTRY', universitiesOf_(''));
  var us = readControls_(UMS.SHEETS.US), ps = readControls_(UMS.SHEETS.PS), de = readDeContext_(), pv = readControls_(UMS.SHEETS.PV);
  refreshUsLists_(us);
  refreshPsLists_(ps);
  refreshDeLists_(de);
  writeHelper_('PV_UNIV', universitiesOf_(pv.country));
}

function refreshUsLists_(f) {
  writeHelper_('US_UNIV', universitiesOf_(f.country));
  var u = universityByName_(f.university, f.country);
  var campuses = [], intakes = [], programs = [];
  if (u) {
    campuses = filterBy_(UMS.SHEETS.CAMPUSES, function (c) { return c.uniId === u.id && isActive_(c); }).map(function (c) { return str_(c.campus); });
    intakes = filterBy_(UMS.SHEETS.INTAKES, function (i) { return i.uniId === u.id && isActive_(i) && (blank_(f.degree) || blank_(i.degree) || i.degree === 'All' || i.degree === f.degree); }).map(function (i) { return str_(i.intake); });
    programs = filterBy_(UMS.SHEETS.PROGRAMS, function (p) {
      return p.uniId === u.id && isActive_(p) && (blank_(f.degree) || p.degree === f.degree) && (blank_(f.campus) || blank_(p.campus) || norm_(p.campus) === norm_(f.campus));
    }).map(programLabel_).sort();
  }
  writeHelper_('US_CAMPUS', campuses);
  writeHelper_('US_INTAKE', intakes);
  writeHelper_('US_PROGRAM', programs);
}

function refreshPsLists_(f) {
  writeHelper_('PS_UNIV', universitiesOf_(f.country));
  var cn = norm_(f.country), un = norm_(f.university);
  var intakes = filterBy_(UMS.SHEETS.INTAKES, function (i) {
    return isActive_(i) && (!cn || norm_(i.country) === cn) && (!un || norm_(i.university) === un);
  }).map(function (i) { return str_(i.term); });
  writeHelper_('PS_INTAKE', uniq_(intakes).sort(function (a, b) { return getList_('INTAKE_TERM').indexOf(a) - getList_('INTAKE_TERM').indexOf(b); }));
  var majors = filterBy_(UMS.SHEETS.PROGRAMS, function (p) {
    return isActive_(p) && (!cn || norm_(p.country) === cn) && (!un || norm_(p.university) === un) && (blank_(f.degree) || p.degree === f.degree);
  }).map(function (p) { return str_(p.major) || str_(p.program); });
  writeHelper_('PS_MAJOR', uniq_(majors).sort());
}

function refreshDeLists_(f) {
  writeHelper_('DE_UNIV', universitiesOf_(f.ctxCountry));
  var u = universityByName_(f.ctxUniversity, f.ctxCountry);
  var progs = [], intakes = [], rounds = [], campuses = [], schs = [];
  if (u) {
    progs = filterBy_(UMS.SHEETS.PROGRAMS, function (p) { return p.uniId === u.id && (blank_(f.ctxDegree) || p.degree === f.ctxDegree); }).map(programLabel_).sort();
    intakes = filterBy_(UMS.SHEETS.INTAKES, function (i) { return i.uniId === u.id; }).map(function (i) { return str_(i.intake); });
    campuses = filterBy_(UMS.SHEETS.CAMPUSES, function (c) { return c.uniId === u.id; }).map(function (c) { return str_(c.campus); });
    schs = filterBy_(UMS.SHEETS.SCHOLARSHIPS, function (s) { return s.uniId === u.id; }).map(function (s) { return str_(s.name); });
    var it = intakeByName_(u.id, f.ctxIntake);
    if (it) rounds = filterBy_(UMS.SHEETS.ROUNDS, function (r) { return r.intakeId === it.id; }).map(function (r) { return str_(r.round); });
  }
  writeHelper_('DE_PROGRAM', progs);
  writeHelper_('DE_INTAKE', intakes);
  writeHelper_('DE_ROUND', rounds);
  writeHelper_('DE_CAMPUS', campuses);
  writeHelper_('DE_SCH', schs);
}

/* ============================================================================
 *  CONTROL LAYOUTS — the search panels of each dashboard
 *  Every control has a key; positions are computed from the layout so that
 *  the code and the drawn sheet always agree.  Named ranges are also created.
 * ========================================================================== */

var CONTROLS = {};
CONTROLS[UMS.SHEETS.US] = {
  mode: 'wide',
  rows: [
    [['country', 'Country', 2, { helper: 'COUNTRIES' }], ['university', 'University', 2, { helper: 'US_UNIV' }],
     ['campus', 'Campus', 2, { helper: 'US_CAMPUS' }], ['degree', 'Degree Level', 2, { list: 'DEGREE' }],
     ['intake', 'Intake', 2, { helper: 'US_INTAKE' }], ['program', 'Program', 2, { helper: 'US_PROGRAM' }]]
  ],
  buttons: [['🔍  SEARCH / REFRESH', 'primary', 'btnSearchUniversity', 3], ['✕  CLEAR FILTERS', 'light', 'btnClearUniversitySearch', 3]],
  hint: 'Pick a Country → University. Results update automatically. Campus, Degree, Intake and Program narrow the view.'
};
CONTROLS[UMS.SHEETS.PS] = {
  mode: 'wide',
  rows: [
    [['country', 'Country', 2, { helper: 'COUNTRIES' }], ['university', 'University', 2, { helper: 'PS_UNIV' }],
     ['degree', 'Degree Level', 2, { list: 'DEGREE' }], ['major', 'Major / Field (type or pick)', 2, { helper: 'PS_MAJOR', loose: true }],
     ['intake', 'Intake', 2, { helper: 'PS_INTAKE' }], ['language', 'Language of Instruction', 2, { list: 'LANG_INSTR' }]],
    [['ielts', 'Student IELTS', 2, { number: [0, 9] }], ['toefl', 'Student TOEFL iBT', 2, { number: [0, 120] }],
     ['pte', 'Student PTE', 2, { number: [10, 90] }], ['topik', 'Student TOPIK Level', 2, { number: [1, 6] }],
     ['gpa', 'Student GPA (meets minimum)', 2, { number: [0, 100] }], ['scholarship', 'Scholarship Available', 2, { list: 'ANYYESNO' }]],
    [['currency', 'Tuition Currency', 2, { list: 'CURRENCY' }], ['tuitionMin', 'Tuition From', 2, { number: [0, 1e12] }],
     ['tuitionMax', 'Tuition To', 2, { number: [0, 1e12] }], ['sort', 'Sort Results By', 2, { list: 'SORT' }]]
  ],
  buttons: [['🔍  SEARCH', 'primary', 'btnSearchPrograms', 2], ['✕  CLEAR', 'light', 'btnClearProgramSearch', 2]],
  buttonsInLastRow: true,
  hint: 'Leave any filter blank to ignore it. Test scores: shows programs whose minimum requirement the student meets.'
};
CONTROLS[UMS.SHEETS.PV] = {
  mode: 'print',
  rows: [
    [['country', 'Country', 3, { helper: 'COUNTRIES' }], ['university', 'University', 4, { helper: 'PV_UNIV' }]]
  ],
  buttons: [['▶  GENERATE', 'primary', 'btnGenerateProfile', 3]],
  buttonsInLastRow: true,
  extraButtons: [['⬇  EXPORT PDF (A4)', 'success', 'exportProfilePdf', 3]],
  hint: 'Print: File → Print → A4 · Portrait · Scale "Fit to width". Or press EXPORT PDF to save a PDF in Google Drive.'
};

/** Draws a search/filter panel and returns control positions (canvas rows). */
function drawControlPanel_(cv, spec, title) {
  var C = UMS.C, n = cv.n, pos = {};
  var top = cv.row(28, C.PAGE); cv.fill(top, 1, n, C.CARD);
  cv.put(top, 1, n, title, { bg: C.CARD, fc: C.NAVY, b: true, fs: 11 });
  cv.line(top, 1, n, 'bottom', C.LINE_L);
  spec.rows.forEach(function (row, ri) {
    var isLast = ri === spec.rows.length - 1;
    var lr = cv.row(20); cv.fill(lr, 1, n, C.CARD);
    var ir = cv.row(30); cv.fill(ir, 1, n, C.CARD);
    var c = 1;
    row.forEach(function (f) {
      cv.put(lr, c, f[2], f[1].toUpperCase(), { bg: C.CARD, fc: C.LABEL, b: true, fs: 8, va: 'bottom' });
      cv.put(ir, c, f[2], '', { bg: C.BLUE_XL, fc: C.TEXT, fs: 10 });
      cv.box(ir, c, ir, c + f[2] - 1, C.INPUT_BORDER, 'SOLID');
      cv.validate(ir, c, f[3]);
      pos[f[0]] = [ir, c];
      c += f[2];
    });
    if (isLast && spec.buttonsInLastRow) {
      var bc = n + 1;
      for (var i = spec.buttons.length - 1; i >= 0; i--) bc -= spec.buttons[i][3];
      spec.buttons.forEach(function (b) { drawButton_(cv, ir, bc, b[3], b[0], b[1], b[2]); bc += b[3]; });
    }
  });
  var sp = cv.row(8); cv.fill(sp, 1, n, C.CARD);
  var hr = cv.row(30); cv.fill(hr, 1, n, C.CARD);
  var btns = spec.buttonsInLastRow ? (spec.extraButtons || []) : spec.buttons;
  var used = btns.reduce(function (a, b) { return a + b[3]; }, 0);
  cv.put(hr, 1, n - used, 'ⓘ  ' + spec.hint, { bg: C.CARD, fc: C.MUTED, i: true, fs: 9, wrap: true });
  var bc2 = n - used + 1;
  btns.forEach(function (b) { drawButton_(cv, hr, bc2, b[3], b[0], b[1], b[2]); bc2 += b[3]; });
  var bottom = cv.row(8); cv.fill(bottom, 1, n, C.CARD);
  cv.box(top, 1, bottom, n, C.LINE, 'SOLID');
  cv.spacer(14);
  return pos;
}

/** Builds the static top part (header + panel) of a search sheet. */
function buildSearchChrome_(sheetName) {
  var spec = CONTROLS[sheetName];
  var cv = new Canvas(spec.mode);
  var titles = {};
  titles[UMS.SHEETS.US] = ['UNIVERSITY SEARCH', 'Search complete university information', '🔎  SEARCH FILTERS'];
  titles[UMS.SHEETS.PS] = ['PROGRAM SEARCH', 'Search programs across all countries and universities', '🎯  PROGRAM FILTERS'];
  titles[UMS.SHEETS.PV] = ['UNIVERSITY PROFILE — PRINT VIEW', 'Printable university information sheet', '🖨  SELECT UNIVERSITY'];
  var t = titles[sheetName];
  drawHeader_(cv, t[0], t[1], sheetName);
  var pos = drawControlPanel_(cv, spec, t[2]);
  return { canvas: cv, pos: pos, resultsRow: cv.rowCount() + 1 };
}

var _LAYOUT_CACHE = {};
function controlLayout_(sheetName) {
  if (!_LAYOUT_CACHE[sheetName]) _LAYOUT_CACHE[sheetName] = buildSearchChrome_(sheetName);
  return _LAYOUT_CACHE[sheetName];
}

/** Reads the current values of a search panel: {country:'..', ...} */
function readControls_(sheetName) {
  var out = {};
  var sh = ss_().getSheetByName(sheetName);
  if (!sh) return out;
  var L = controlLayout_(sheetName);
  var maxR = 0;
  for (var k in L.pos) maxR = Math.max(maxR, L.pos[k][0] + 1);
  if (sh.getMaxRows() < maxR) return out;
  var vals = sh.getRange(1, 1, maxR, GRID[CONTROLS[sheetName].mode].cols + 2).getValues();
  for (var k2 in L.pos) {
    var v = vals[L.pos[k2][0]][L.pos[k2][1]];
    out[k2] = (typeof v === 'string') ? v.trim() : v;
  }
  return out;
}
function setControl_(sheetName, key, value) {
  var L = controlLayout_(sheetName);
  var p = L.pos[key];
  if (!p) return;
  getSheet_(sheetName).getRange(p[0] + 1, p[1] + 1).setValue(value === undefined ? '' : value);
}
/** Which control (if any) sits at a given sheet cell. */
function controlAt_(sheetName, row, col) {
  if (!CONTROLS[sheetName]) return null;
  var L = controlLayout_(sheetName);
  for (var k in L.pos) if (L.pos[k][0] + 1 === row && L.pos[k][1] + 1 === col) return k;
  return null;
}


/* ============================================================================
 *  HOME DASHBOARD
 * ========================================================================== */

function a1_(r, c) { return colLetter_(c + 1) + (r + 1); }              // canvas (row, content col) → A1
function colRef_(table, key) {
  var t = readTable_(table);
  var i = t.idx[key];
  if (i === undefined) return null;
  var L = colLetter_(i + 1);
  return "'" + table + "'!$" + L + ':$' + L;
}

var HOME_CARDS = [
  ['🔍', 'UNIVERSITY SEARCH', 'Search universities, intakes, programs, fees and requirements', 'navUniversitySearch', '#1D4ED8', 'University Search'],
  ['📚', 'PROGRAM SEARCH', 'Search programs across countries and universities', 'navProgramSearch', '#0F766E', 'Program Search'],
  ['✎', 'DATA ENTRY', 'Add or update university information', 'navDataEntry', '#B45309', 'University Data Entry'],
  ['🖨', 'UNIVERSITY PROFILE', 'Generate a printable university profile', 'navPrintView', '#6D28D9', 'Print View'],
  ['🗄', 'DATABASE', 'Admin database', 'navDatabase', '#163A5F', '03_Universities'],
  ['⚙', 'SETTINGS', 'System configuration', 'navSettings', '#475569', '01_Settings']
];

function buildHomeTop_() {
  var S = UMS.SHEETS, C = UMS.C;
  var cv = new Canvas('wide');
  drawHeader_(cv, str_(setting_('System Name')) || 'UNIVERSITY MANAGEMENT SYSTEM', str_(setting_('Subtitle')) || 'International Education Consultancy', 'HOME');

  /* ---- QUICK ACCESS cards (2 rows × 3) ---------------------------------- */
  var lab = cv.row(24);
  cv.put(lab, 1, 12, 'QUICK ACCESS', { fc: C.LABEL, b: true, fs: 9, va: 'bottom' });
  for (var block = 0; block < 2; block++) {
    var r1 = cv.row(6), r2 = cv.row(30), r3 = cv.row(34), r4 = cv.row(24);
    for (var k = 0; k < 3; k++) {
      var card = HOME_CARDS[block * 3 + k];
      var c = 1 + k * 4;
      cv.fill(r1, c, c + 3, card[4]);
      cv.put(r2, c, 4, '  ' + card[0] + '   ' + card[1], { bg: C.CARD, fc: card[4], b: true, fs: 13 });
      cv.put(r3, c, 4, '     ' + card[2], { bg: C.CARD, fc: C.MUTED, fs: 9, wrap: true, va: 'top' });
      cv.put(r4, c, 4, '     Open  ›', { bg: C.CARD, fc: card[4], b: true, fs: 9, link: sheetLink_(card[5]) });
      cv.box(r1, c, r4, c + 3, C.PAGE, 'SOLID_THICK');
      cv.button(r1, c, 4, card[3], 4);
    }
    cv.spacer(8);
  }

  /* ---- KPI cards (2 rows × 4) ------------------------------------------ */
  var lab2 = cv.row(24);
  cv.put(lab2, 1, 12, 'OVERVIEW  ·  live figures from the database', { fc: C.LABEL, b: true, fs: 9, va: 'bottom' });
  var nu = function (t, k) { return colRef_(t, k); };
  var statusSet = '{"Pending Verification","Need Update","Expired"}';
  var needParts = STATUS_TABLES.map(function (t) { var r = nu(t, 'status'); return r ? 'SUMPRODUCT(COUNTIF(' + r + ',' + statusSet + '))' : null; }).filter(function (x) { return !!x; });
  var KPI = [
    ['TOTAL COUNTRIES', '=COUNTIFS(' + nu(S.COUNTRIES, 'active') + ',"Yes",' + nu(S.COUNTRIES, 'status') + ',"<>Archived")', 'Active destination countries', C.BLUE],
    ['TOTAL UNIVERSITIES', '=COUNTIFS(' + nu(S.UNIVERSITIES, 'id') + ',"?*",' + nu(S.UNIVERSITIES, 'status') + ',"<>Archived")-1', 'Universities in database', C.TEAL],
    ['TOTAL PROGRAMS', '=COUNTIFS(' + nu(S.PROGRAMS, 'id') + ',"?*",' + nu(S.PROGRAMS, 'status') + ',"<>Archived")-1', 'All degree levels', C.PURPLE],
    ['SCHOLARSHIPS', '=COUNTIFS(' + nu(S.SCHOLARSHIPS, 'id') + ',"?*",' + nu(S.SCHOLARSHIPS, 'status') + ',"<>Archived")-1', 'Scholarship schemes', C.AMBER],
    ["MASTER'S PROGRAMS", '=COUNTIFS(' + nu(S.PROGRAMS, 'degree') + ',"Master\'s",' + nu(S.PROGRAMS, 'status') + ',"<>Archived")', 'Postgraduate taught / research', C.BLUE],
    ["BACHELOR'S PROGRAMS", '=COUNTIFS(' + nu(S.PROGRAMS, 'degree') + ',"Bachelor\'s",' + nu(S.PROGRAMS, 'status') + ',"<>Archived")', 'Undergraduate programs', C.TEAL],
    ['UPCOMING DEADLINES', '=COUNTIFS(' + nu(S.ROUNDS, 'appEnd') + ',">="&TODAY(),' + nu(S.ROUNDS, 'status') + ',"<>Archived")', 'Application rounds still open', C.ROSE],
    ['RECORDS NEEDING UPDATE', '=' + needParts.join('+'), 'Pending · Need Update · Expired', '#9A3412']
  ];
  for (var kr = 0; kr < 2; kr++) {
    var a = cv.row(24), b = cv.row(40), d = cv.row(22);
    for (var j = 0; j < 4; j++) {
      var kp = KPI[kr * 4 + j], cc = 1 + j * 3;
      cv.put(a, cc, 3, '   ' + kp[0], { bg: C.CARD, fc: C.LABEL, b: true, fs: 8, va: 'bottom' });
      cv.put(b, cc, 3, '', { bg: C.CARD, fc: kp[3], b: true, fs: 24 });
      cv.formula(b, cc, kp[1], '"   "#,##0');
      cv.put(d, cc, 3, '   ' + kp[2], { bg: C.CARD, fc: C.MUTED, fs: 8, va: 'top' });
      cv.box(a, cc, d, cc + 2, C.PAGE, 'SOLID_THICK');
      cv.line(a, cc, cc + 2, 'top', kp[3], 'SOLID_THICK');
    }
    cv.spacer(8);
  }

  return cv;
}

/** Dynamic lower half of HOME. `base` = sheet row offset where it is drawn. */
function buildHomeBody_(base) {
  var S = UMS.SHEETS, C = UMS.C;
  var cv = new Canvas('wide');
  cv.base = base;
  var nu = function (t, k) { return colRef_(t, k); };
  var a1_ = function (r, c) { return cv.a1(r, c); };
  /* ---- COUNTRY SUMMARY  +  DATA QUALITY -------------------------------- */
  cv.spacer(6);
  var st = cv.row(28);
  cv.put(st, 1, 6, '🌍  COUNTRY SUMMARY', { bg: '#E8EEF7', fc: C.NAVY, b: true, fs: 11 });
  cv.put(st, 7, 6, '🛡  DATA QUALITY  ·  records by status (all tables)', { bg: '#E8EEF7', fc: C.NAVY, b: true, fs: 11 });
  cv.line(st, 1, 6, 'bottom', C.BLUE, 'SOLID_MEDIUM');
  cv.line(st, 7, 12, 'bottom', C.TEAL, 'SOLID_MEDIUM');
  cv.line(st, 7, 7, 'left', C.PAGE, 'SOLID_THICK');
  var th = cv.row(24);
  [['Country', 3, 'left'], ['Universities', 1, 'center'], ['Programs', 1, 'center'], ['Scholarships', 1, 'center']].reduce(function (c0, h) {
    cv.put(th, c0, h[1], h[0], { bg: C.HEAD_BG, fc: C.LABEL, b: true, fs: 9, ha: h[2] }); return c0 + h[1];
  }, 1);
  [['Status', 3, 'left'], ['Records', 2, 'center'], ['', 1, 'center']].reduce(function (c0, h) {
    cv.put(th, c0, h[1], h[0], { bg: C.HEAD_BG, fc: C.LABEL, b: true, fs: 9, ha: h[2] }); return c0 + h[1];
  }, 7);
  cv.line(th, 7, 7, 'left', C.PAGE, 'SOLID_THICK');

  var countries = readTable_(S.COUNTRIES).objs.filter(function (c) { return String(c.active) !== 'No' && isActive_(c); })
    .map(function (c) { return str_(c.country); }).sort();
  var statuses = getList_('STATUS');
  var nRows = Math.max(countries.length, statuses.length + 1, 1);
  var firstBody = null;
  for (var i = 0; i < nRows; i++) {
    var r = cv.row(24);
    if (firstBody === null) firstBody = r;
    var bg = i % 2 ? C.ZEBRA : C.CARD;
    cv.fill(r, 1, 12, C.CARD);
    if (i < countries.length) {
      cv.put(r, 1, 3, '  ' + countries[i], { bg: bg, fc: C.TEXT, b: true, fs: 10 });
      var ref = '$' + a1_(r, 1).replace(/(\d+)$/, '$$$1');
      [[S.UNIVERSITIES, 4], [S.PROGRAMS, 5], [S.SCHOLARSHIPS, 6]].forEach(function (x) {
        cv.put(r, x[1], 1, '', { bg: bg, fc: C.NAVY, b: true, ha: 'center' });
        cv.formula(r, x[1], '=COUNTIFS(' + nu(x[0], 'country') + ',TRIM(' + ref + '),' + nu(x[0], 'status') + ',"<>Archived")', '0');
      });
    } else if (i === 0) {
      cv.put(r, 1, 6, 'No active countries yet — add one in Data Entry.', { bg: bg, fc: C.MUTED, i: true, ha: 'center' });
    } else {
      cv.fill(r, 1, 6, bg);
    }
    if (i < statuses.length) {
      var s = statuses[i], stl = UMS.STATUS_STYLE[s] || { bg: C.CARD, fc: C.TEXT };
      cv.put(r, 7, 3, '  ● ' + s, { bg: stl.bg, fc: stl.fc, b: true });
      var parts = STATUS_TABLES.map(function (t) { var rr = nu(t, 'status'); return rr ? 'COUNTIF(' + rr + ',"' + s + '")' : null; }).filter(function (x) { return !!x; });
      cv.put(r, 10, 2, '', { bg: bg, fc: C.NAVY, b: true, fs: 11, ha: 'center' });
      cv.formula(r, 10, '=' + parts.join('+'), '#,##0');
      cv.fill(r, 12, 12, bg);
    } else if (i === statuses.length) {
      cv.put(r, 7, 3, '  Σ  Total records', { bg: C.HEAD_BG, fc: C.NAVY, b: true });
      cv.put(r, 10, 2, '', { bg: C.HEAD_BG, fc: C.NAVY, b: true, fs: 11, ha: 'center' });
      cv.formula(r, 10, '=SUM(' + a1_(firstBody, 10) + ':' + a1_(r - 1, 10) + ')', '#,##0');
      cv.fill(r, 12, 12, C.HEAD_BG);
    } else cv.fill(r, 7, 12, bg);
    cv.line(r, 7, 7, 'left', C.PAGE, 'SOLID_THICK');
  }
  var lastBody = cv.rowCount() - 1;
  cv.box(th, 1, lastBody, 6, C.LINE, 'SOLID', { h: true, color: C.LINE_L });
  cv.box(th, 7, lastBody, 12, C.LINE, 'SOLID', { h: true, color: C.LINE_L });
  cv.line(th, 7, 7, 'left', C.PAGE, 'SOLID_THICK');
  for (var q = th; q <= lastBody; q++) cv.line(q, 7, 7, 'left', C.PAGE, 'SOLID_THICK');
  cv.spacer(18);

  /* ---- UPCOMING DEADLINES --------------------------------------------- */
  var orange = Number(setting_('Deadline Warning Days (Orange)')) || 7;
  var yellow = Number(setting_('Deadline Notice Days (Yellow)')) || 30;
  var back = Number(setting_('Show Passed Deadlines For (days)')) || 14;
  var maxRows = Number(setting_('Home Deadline Rows')) || 20;
  drawSectionTitle_(cv, '⏰  UPCOMING APPLICATION DEADLINES', C.ROSE, '🔴 passed   🟠 ≤ ' + orange + ' days   🟡 ≤ ' + yellow + ' days');
  var rounds = readTable_(S.ROUNDS).objs.filter(function (r0) {
    if (!isActive_(r0) || !(r0.appEnd instanceof Date)) return false;
    var dd = daysUntil_(r0.appEnd);
    return dd >= -back;
  }).sort(function (x, y) { return x.appEnd - y.appEnd; });
  // passed ones go last so the nearest open deadline is always first
  var open = rounds.filter(function (r0) { return daysUntil_(r0.appEnd) >= 0; });
  var passed = rounds.filter(function (r0) { return daysUntil_(r0.appEnd) < 0; }).reverse();
  rounds = open.concat(passed).slice(0, maxRows);

  var cols = [{ h: 'University', span: 3 }, { h: 'Country', span: 2 }, { h: 'Intake', span: 2 }, { h: 'Round', span: 1 },
    { h: 'Application End', span: 2, align: 'center' }, { h: 'Days Remaining', span: 1, align: 'center' }, { h: 'Alert', span: 1, align: 'center' }];
  var rows = rounds.map(function (r0) {
    var ds = deadlineStyle_(r0.appEnd) || {};
    return [{ t: r0.university, b: true }, r0.country, r0.intake, r0.round, { t: '', ha: 'center' },
      { t: '', ha: 'center', b: true }, { t: '', ha: 'center', b: true, fs: 8 }];
  });
  var tbl = drawTable_(cv, cols, rows, { empty: 'No upcoming deadlines. Add intakes and application rounds in Data Entry.' });
  var dl = { first: tbl.first, last: tbl.last, n: rounds.length };
  rounds.forEach(function (r0, i2) {
    var rr = tbl.first + i2;
    cv.raw(rr, 9, r0.appEnd, 'dd mmm yyyy');
    cv.formula(rr, 11, '=IF(ISNUMBER(' + a1_(rr, 9) + '),' + a1_(rr, 9) + '-TODAY(),"")', '0');
    cv.formula(rr, 12, '=IF(' + a1_(rr, 11) + '="","",IF(' + a1_(rr, 11) + '<0,"PASSED",IF(' + a1_(rr, 11) + '<=' + orange +
      ',"URGENT",IF(' + a1_(rr, 11) + '<=' + yellow + ',"SOON","OPEN"))))');
  });
  cv.spacer(18);

  /* ---- RECENT ACTIVITY ------------------------------------------------- */
  drawSectionTitle_(cv, '🕘  RECENT ACTIVITY', C.NAVY2, 'Full history: 15_Audit_Log');
  var audit = readTable_(S.AUDIT).objs.filter(function (a0) { return a0.action !== 'ERROR'; }).slice(-6).reverse();
  drawTable_(cv, [{ h: 'When', span: 2 }, { h: 'User', span: 2 }, { h: 'Action', span: 2 }, { h: 'Record ID', span: 2 }, { h: 'University / Detail', span: 4 }],
    audit.map(function (a0) {
      return [a0.ts instanceof Date ? Utilities.formatDate(a0.ts, tz_(), 'dd MMM yyyy HH:mm') : str_(a0.ts), a0.user, { t: a0.action, b: true }, a0.recordId,
        str_(a0.university) || str_(a0.country) || str_(a0.newValue).substring(0, 80)];
    }), { empty: 'No activity yet.' });
  cv.spacer(10);
  var foot = cv.row(20);
  cv.put(foot, 1, 12, 'University Management System v' + UMS.VERSION + '  ·  Sample records are labelled "' + UMS.SAMPLE_FLAG + '" and are NOT real admission data.',
    { fc: C.MUTED, fs: 8, i: true, ha: 'center' });
  return { canvas: cv, deadlines: dl };
}

/** Row where the dynamic part of HOME starts (top part is fixed). */
function homeBodyRow_() { return buildHomeTop_().rowCount() + 1; }

/**
 * Renders HOME.  full=true redraws everything including click-buttons
 * (setup); otherwise only the live lower half is refreshed (fast).
 */
function renderHome_(full) {
  var sh = getSheet_(UMS.SHEETS.HOME);
  var top = buildHomeTop_();
  var bodyRow = top.rowCount() + 1;
  if (full) {
    removeButtons_(sh);
    clearRegion_(sh, 1);
    sizeColumns_(sh, 'wide');
    top.flush(sh, 1);
    sh.setFrozenRows(5);
  } else {
    clearRegion_(sh, bodyRow);
  }
  var built = buildHomeBody_(bodyRow - 1);
  built.canvas.flush(sh, bodyRow);
  finishPage_(sh, bodyRow + built.canvas.rowCount() - 1, 'wide');

  // Conditional formatting for deadline alerts (live — based on TODAY())
  var rules = [];
  var dl = built.deadlines;
  if (dl.n > 0) {
    var r0 = bodyRow + dl.first;
    var rg = sh.getRange(r0, 2, dl.n, 12);
    var dcol = colLetter_(12);
    var orange = Number(setting_('Deadline Warning Days (Orange)')) || 7;
    var yellow = Number(setting_('Deadline Notice Days (Yellow)')) || 30;
    rules.push(SpreadsheetApp.newConditionalFormatRule().whenFormulaSatisfied('=AND(ISNUMBER($' + dcol + r0 + '),$' + dcol + r0 + '<0)')
      .setBackground('#FEE2E2').setFontColor('#991B1B').setRanges([rg]).build());
    rules.push(SpreadsheetApp.newConditionalFormatRule().whenFormulaSatisfied('=AND(ISNUMBER($' + dcol + r0 + '),$' + dcol + r0 + '<=' + orange + ')')
      .setBackground('#FFEDD5').setFontColor('#9A3412').setRanges([rg]).build());
    rules.push(SpreadsheetApp.newConditionalFormatRule().whenFormulaSatisfied('=AND(ISNUMBER($' + dcol + r0 + '),$' + dcol + r0 + '<=' + yellow + ')')
      .setBackground('#FEF9C3').setFontColor('#854D0E').setRanges([rg]).build());
  }
  sh.setConditionalFormatRules(rules);
  if (full) placeButtons_(sh, top, 1);
}


/* ============================================================================
 *  UNIVERSITY DATA GATHERING  (shared by University Search and Print View)
 * ========================================================================== */

/** Program name, plus the major on a 2nd line only if it adds information. */
function programTitle_(p) {
  var name = str_(p.program), major = str_(p.major);
  if (!major || norm_(name).indexOf(norm_(major)) >= 0) return name;
  return name + '\n' + major;
}
function preferSpecific_(list, program) {
  var spec = list.filter(function (x) { return String(x.programId) === String(program.id); });
  return spec.length ? spec : list.filter(function (x) { return appliesTo_(x, program); });
}
function scopeLabel_(rec, programsById) {
  if (str_(rec.programId).toUpperCase() === 'ALL' || blank_(rec.programId)) return 'All ' + (blank_(rec.degree) || rec.degree === 'All' ? 'programs' : rec.degree + ' programs');
  var p = programsById[rec.programId];
  return p ? str_(p.program) : str_(rec.program || rec.programId);
}

/**
 * Collects everything about one university, honouring optional filters
 * {campus, degree, intake, program}.
 */
function gatherUniversity_(u, f) {
  f = f || {};
  var S = UMS.SHEETS;
  var d = { u: u };
  var mine = function (t) { return filterBy_(t, function (o) { return o.uniId === u.id && isActive_(o); }); };
  d.campuses = mine(S.CAMPUSES);
  var allPrograms = mine(S.PROGRAMS);
  d.programsById = {};
  allPrograms.forEach(function (p) { d.programsById[p.id] = p; });

  var progSel = f.program ? programByLabel_(u.id, f.program) : null;
  d.intakes = mine(S.INTAKES).filter(function (i) {
    return (blank_(f.intake) || norm_(i.intake) === norm_(f.intake)) &&
      (blank_(f.degree) || blank_(i.degree) || i.degree === 'All' || i.degree === f.degree);
  });
  var intakeIds = {};
  d.intakes.forEach(function (i) { intakeIds[i.id] = i; });
  d.rounds = mine(S.ROUNDS).filter(function (r) { return intakeIds[r.intakeId]; })
    .sort(function (a, b) {
      var ia = intakeIds[a.intakeId], ib = intakeIds[b.intakeId];
      var ya = Number(ia.year) || 0, yb = Number(ib.year) || 0;
      if (ya !== yb) return ya - yb;
      var ta = a.appStart instanceof Date ? a.appStart.getTime() : (a.appEnd instanceof Date ? a.appEnd.getTime() : 9e15);
      var tb = b.appStart instanceof Date ? b.appStart.getTime() : (b.appEnd instanceof Date ? b.appEnd.getTime() : 9e15);
      return ta - tb;
    });
  d.intakesWithoutRounds = d.intakes.filter(function (i) { return !d.rounds.some(function (r) { return r.intakeId === i.id; }); });

  d.programs = allPrograms.filter(function (p) {
    if (progSel && p.id !== progSel.id) return false;
    if (!blank_(f.degree) && p.degree !== f.degree) return false;
    if (!blank_(f.campus) && !blank_(p.campus) && norm_(p.campus) !== norm_(f.campus)) return false;
    if (!blank_(f.intake)) {
      var it = first_(d.intakes, function (i) { return norm_(i.intake) === norm_(f.intake); });
      var txt = norm_(p.intakes);
      if (txt && it && txt.indexOf(norm_(it.term)) < 0 && txt.indexOf(norm_(it.intake)) < 0) return false;
    }
    return true;
  }).sort(function (a, b) { return str_(a.program).localeCompare(str_(b.program)); });

  var fees = mine(S.FEES), lang = mine(S.LANGUAGE), schs = mine(S.SCHOLARSHIPS), acad = mine(S.ACADEMIC), docs = mine(S.DOCUMENTS);
  var relevant = function (rec) {
    if (progSel) return appliesTo_(rec, progSel);
    if (!blank_(f.degree)) return blank_(rec.degree) || rec.degree === 'All' || rec.degree === f.degree;
    return true;
  };
  d.fees = fees.filter(relevant);
  d.lang = lang.filter(relevant);
  d.schs = schs.filter(relevant);
  d.acad = acad.filter(relevant);
  d.docs = docs.filter(relevant);
  var schIds = {};
  d.schs.forEach(function (s) { schIds[s.id] = true; });
  d.tiers = filterBy_(S.TIERS, function (t) { return schIds[t.schId] && isActive_(t); });

  // per-program derived info
  d.programs.forEach(function (p) {
    var pf = preferSpecific_(fees, p);
    var tu = first_(pf, function (x) { return x.feeType === 'Tuition Fee'; });
    var ap = first_(pf, function (x) { return x.feeType === 'Application Fee'; });
    p._tuition = tu; p._appFee = ap;
    p._schs = schs.filter(function (s) { return appliesTo_(s, p); });
    var ac = preferSpecific_(acad, p);
    p._acad = ac.length ? ac[0] : null;
  });

  d.csr = filterBy_(S.CSR, function (c) {
    return isActive_(c) && (c.countryId === u.countryId || norm_(c.country) === norm_(u.country)) &&
      (blank_(c.uniId) || str_(c.uniId).toUpperCase() === 'ALL' || c.uniId === u.id) &&
      (blank_(f.degree) || blank_(c.degree) || c.degree === 'All' || c.degree === f.degree);
  });
  d.visa = filterBy_(S.VISA, function (v) { return isActive_(v) && (v.countryId === u.countryId || norm_(v.country) === norm_(u.country)); });
  d.sources = mine(S.SOURCES);
  return d;
}

/* ============================================================================
 *  PROFILE SECTIONS — drawn for University Search ('wide') and Print View
 * ========================================================================== */

/** Draws a table whose columns carry a span per mode (0 = hidden in that mode). */
function drawModeTable_(cv, defs, items, empty) {
  var cols = defs.filter(function (d) { return (d.w[cv.mode] || 0) > 0; });
  var rows = items.map(function (it) { return cols.map(function (d) { return d.v(it); }); });
  return drawTable_(cv, cols.map(function (d) { return { h: d.h, span: d.w[cv.mode], align: d.align }; }), rows, { empty: empty });
}
function W(wide, print) { return { wide: wide, print: print }; }
function dash_(v) { return blank_(v) ? '—' : v; }

function drawProfileCard_(cv, u, campuses) {
  var C = UMS.C, n = cv.n, isP = cv.mode === 'print';
  var top = cv.row(10); cv.fill(top, 1, n, C.CARD);
  var logoSpan = 2;
  var rows = [cv.row(isP ? 42 : 36), cv.row(22), cv.row(26), cv.row(26), cv.row(26)];
  rows.forEach(function (r) { cv.fill(r, 1, n, C.CARD); });
  var initials = str_(u.name).replace(/\(.*?\)/g, '').split(/\s+/).filter(function (w) { return /^[A-Za-z]/.test(w) && w.length > 2; })
    .slice(0, 2).map(function (w) { return w.charAt(0).toUpperCase(); }).join('') || '🎓';
  cv.put(rows[0], 1, logoSpan, blank_(u.logo) ? initials : '', { bg: blank_(u.logo) ? C.BLUE : C.CARD, fc: C.WHITE, b: true, fs: isP ? 22 : 28, ha: 'center', rs: 5 });
  if (!blank_(u.logo)) cv.formula(rows[0], 1, '=IMAGE("' + str_(u.logo).replace(/"/g, '') + '",1)');
  var rest = n - logoSpan, c0 = logoSpan + 1;
  var badgeSpan = isP ? 3 : 3;
  cv.put(rows[0], c0, rest - badgeSpan, '  ' + u.name, { bg: C.CARD, fc: C.NAVY, b: true, fs: isP ? 13 : 18, wrap: isP });
  var st = statusCell_(u.status);
  cv.put(rows[0], n - badgeSpan + 1, badgeSpan, st.t, { bg: st.bg || C.CARD, fc: st.fc, b: true, fs: 9, ha: 'center' });
  var loc = [u.city, u.country].filter(function (x) { return !blank_(x); }).join(', ');
  cv.put(rows[1], c0, rest - badgeSpan, '  📍 ' + loc + (blank_(u.type) ? '' : '   ·   ' + u.type + ' university'), { bg: C.CARD, fc: C.MUTED, fs: 10 });
  if (sampleBadge_(u)) cv.put(rows[1], n - badgeSpan + 1, badgeSpan, '⚠ ' + UMS.SAMPLE_FLAG, { bg: C.PURPLE_L, fc: C.PURPLE, b: true, fs: 8, ha: 'center' });

  var campusText = campuses.map(function (c) { return str_(c.campus); }).join(', ');
  var fieldsWide = [
    [['Campus', campusText, 3], ['Ranking', u.ranking, 1], ['Accreditation', u.accreditation, 3]],
    [['Official Website', u.website, 4, true], ['International Website', u.intlWebsite, 4, true]],
    [['Verification', u.status, 2], ['Last Updated', u.updated instanceof Date ? fmtDate_(u.updated) : u.updated, 2], ['University ID', u.id, 3]]
  ];
  var fieldsPrint = [
    [['Campus', campusText, 2], ['Ranking', u.ranking, 1], ['Accred.', u.accreditation, 2]],
    [['Website', u.website, 2, true], ['Int\'l Website', u.intlWebsite, 4, true]],
    [['Verification', u.status, 2], ['Last Updated', u.updated instanceof Date ? fmtDate_(u.updated) : u.updated, 1], ['ID', u.id, 2]]
  ];
  (isP ? fieldsPrint : fieldsWide).forEach(function (line, li) {
    var r = rows[2 + li], c = c0;
    line.forEach(function (f) {
      cv.put(r, c, 1, '  ' + f[0].toUpperCase(), { bg: C.CARD, fc: C.LABEL, b: true, fs: 7, wrap: true });
      c += 1;
      var v = blank_(f[1]) ? '—' : str_(f[1]);
      var isLink = f[3] && /^https?:\/\//i.test(v);
      cv.put(r, c, f[2], v, { bg: C.CARD, fc: isLink ? C.BLUE : C.TEXT, fs: 9, link: isLink ? v : null, u: isLink, wrap: true });
      c += f[2];
    });
  });
  var bot = cv.row(10); cv.fill(bot, 1, n, C.CARD);
  cv.box(top, 1, bot, n, C.LINE, 'SOLID');
  cv.line(top, 1, n, 'top', C.BLUE, 'SOLID_THICK');
  cv.spacer(14);
}

function drawProfileSections_(cv, d, opts) {
  opts = opts || {};
  var C = UMS.C, u = d.u;
  drawProfileCard_(cv, u, d.campuses);

  /* ---- Application timeline --------------------------------------------- */
  drawSectionTitle_(cv, '🗓  APPLICATION TIMELINE', C.ROSE, cv.mode === 'wide' ? d.rounds.length + ' round(s)' : null);
  var intakeById = {};
  d.intakes.forEach(function (i) { intakeById[i.id] = i; });
  var tlItems = d.rounds.map(function (r) { return { r: r, i: intakeById[r.intakeId] }; })
    .concat(d.intakesWithoutRounds.map(function (i) { return { r: null, i: i }; }));
  drawModeTable_(cv, [
    { h: 'Intake', w: W(2, 2), v: function (x) { return { t: x.i.intake + (x.i.rolling === 'Yes' ? '  (rolling)' : ''), b: true }; } },
    { h: 'Round', w: W(2, 1), v: function (x) { return x.r ? x.r.round : 'No rounds recorded'; } },
    { h: 'Application Start', w: W(2, 2), align: 'center', v: function (x) { return x.r ? dash_(fmtDate_(x.r.appStart)) : '—'; } },
    { h: 'Application End', w: W(2, 2), align: 'center', v: function (x) {
      if (!x.r || !(x.r.appEnd instanceof Date)) return '—';
      var ds = deadlineStyle_(x.r.appEnd);
      return { t: fmtDate_(x.r.appEnd), b: true, bg: ds && ds.bg ? ds.bg : null, fc: ds ? ds.fc : null, ha: 'center' };
    } },
    { h: 'Interview', w: W(2, 2), align: 'center', v: function (x) {
      if (!x.r) return '—';
      var a = fmtDate_(x.r.interviewStart), b = fmtDate_(x.r.interviewEnd);
      return a && b && a !== b ? a + ' – ' + b : dash_(a || b);
    } },
    { h: 'Result', w: W(1, 1), align: 'center', v: function (x) { return x.r ? dash_(fmtDate_(x.r.result)) : '—'; } },
    { h: 'Days Left', w: W(1, 0), align: 'center', v: function (x) {
      if (!x.r || !(x.r.appEnd instanceof Date)) return '—';
      var ds = deadlineStyle_(x.r.appEnd);
      return { t: ds.label, b: true, bg: ds.bg, fc: ds.fc, ha: 'center' };
    } }
  ], tlItems, 'No intakes recorded for this university yet.');
  cv.spacer(14);

  /* ---- Programs by degree --------------------------------------------- */
  var tuition = function (p) { return p._tuition ? fmtMoney_(p._tuition.amount, p._tuition.currency, p._tuition.period) : '—'; };
  var appFee = function (p) { return p._appFee ? fmtMoney_(p._appFee.amount, p._appFee.currency) : '—'; };
  var schText = function (p) {
    if (!p._schs.length) return { t: '—', ha: 'center', fc: C.MUTED };
    return { t: '✔ ' + str_(p._schs[0].name) + (p._schs.length > 1 ? '  (+' + (p._schs.length - 1) + ')' : ''), fc: C.GREEN, b: true };
  };
  var progName = function (p) { return { t: programTitle_(p), b: true }; };
  var bach = d.programs.filter(function (p) { return p.degree === "Bachelor's"; });
  var mast = d.programs.filter(function (p) { return p.degree === "Master's"; });
  var other = d.programs.filter(function (p) { return p.degree !== "Bachelor's" && p.degree !== "Master's"; });

  if (!opts.degree || opts.degree === "Bachelor's") {
    drawSectionTitle_(cv, "🎓  BACHELOR'S PROGRAMS", C.BLUE, cv.mode === 'wide' ? bach.length + ' program(s)' : null);
    drawModeTable_(cv, [
      { h: 'Program', w: W(3, 3), v: progName },
      { h: 'Department', w: W(1, 2), v: function (p) { return dash_(p.department); } },
      { h: 'Campus', w: W(1, 0), v: function (p) { return dash_(p.campus); } },
      { h: 'Duration', w: W(1, 1), align: 'center', v: function (p) { return dash_(p.duration); } },
      { h: 'Language', w: W(1, 1), align: 'center', v: function (p) { return dash_(p.language); } },
      { h: 'Intake', w: W(1, 1), v: function (p) { return dash_(p.intakes); } },
      { h: 'Tuition / Period', w: W(2, 2), v: tuition },
      { h: 'Application Fee', w: W(1, 0), v: appFee },
      { h: 'Scholarship', w: W(1, 0), v: schText }
    ], bach, "No Bachelor's programs recorded.");
    cv.spacer(14);
  }
  if (!opts.degree || opts.degree === "Master's") {
    drawSectionTitle_(cv, "🎓  MASTER'S PROGRAMS", C.TEAL, cv.mode === 'wide' ? mast.length + ' program(s)' : null);
    drawModeTable_(cv, [
      { h: 'Program', w: W(2, 3), v: progName },
      { h: 'Department', w: W(1, 0), v: function (p) { return dash_(p.department); } },
      { h: 'Campus', w: W(1, 0), v: function (p) { return dash_(p.campus); } },
      { h: 'Duration · Language', w: W(1, 0), v: function (p) { return dash_(p.duration) + '\n' + dash_(p.language); } },
      { h: 'Duration', w: W(0, 1), align: 'center', v: function (p) { return dash_(p.duration); } },
      { h: 'Language', w: W(0, 1), align: 'center', v: function (p) { return dash_(p.language); } },
      { h: 'Intake', w: W(1, 1), v: function (p) { return dash_(p.intakes); } },
      { h: 'Tuition / Period', w: W(1, 2), v: tuition },
      { h: 'App. Fee', w: W(1, 0), v: appFee },
      { h: 'Min GPA', w: W(1, 1), align: 'center', v: function (p) { return p._acad && !blank_(p._acad.minGpa) ? fmtDec_(p._acad.minGpa) + (blank_(p._acad.gpaScale) ? '' : ' / ' + p._acad.gpaScale) : '—'; } },
      { h: 'Previous Degree', w: W(1, 0), v: function (p) { return p._acad ? dash_(p._acad.prevDegree) : '—'; } },
      { h: 'Required Background', w: W(1, 1), v: function (p) { return p._acad ? dash_(p._acad.background) : '—'; } },
      { h: 'Scholarship', w: W(1, 0), v: schText }
    ], mast, "No Master's programs recorded.");
    cv.spacer(14);
  }
  if (other.length) {
    drawSectionTitle_(cv, '🎓  OTHER PROGRAMS  (Foundation · Diploma · PhD · Language …)', C.PURPLE, null);
    drawModeTable_(cv, [
      { h: 'Program', w: W(3, 3), v: progName },
      { h: 'Degree', w: W(1, 1), v: function (p) { return p.degree; } },
      { h: 'Department', w: W(2, 2), v: function (p) { return dash_(p.department); } },
      { h: 'Duration', w: W(1, 1), align: 'center', v: function (p) { return dash_(p.duration); } },
      { h: 'Language', w: W(1, 1), align: 'center', v: function (p) { return dash_(p.language); } },
      { h: 'Intake', w: W(1, 0), v: function (p) { return dash_(p.intakes); } },
      { h: 'Tuition / Period', w: W(2, 2), v: tuition },
      { h: 'Scholarship', w: W(1, 0), v: schText }
    ], other, '');
    cv.spacer(14);
  }

  /* ---- Academic requirements ----------------------------------------- */
  drawSectionTitle_(cv, '📘  ACADEMIC REQUIREMENTS', C.NAVY2, null);
  drawModeTable_(cv, [
    { h: 'Applies To', w: W(2, 2), v: function (a) { return { t: scopeLabel_(a, d.programsById), b: true }; } },
    { h: 'Min GPA', w: W(1, 1), align: 'center', v: function (a) { return blank_(a.minGpa) ? '—' : fmtDec_(a.minGpa) + (blank_(a.gpaScale) ? '' : ' / ' + a.gpaScale); } },
    { h: 'Previous Degree', w: W(2, 2), v: function (a) { return dash_(a.prevDegree); } },
    { h: 'Background', w: W(2, 2), v: function (a) { return dash_(a.background); } },
    { h: 'Min %', w: W(1, 0), align: 'center', v: function (a) { return blank_(a.minPercent) ? '—' : a.minPercent + '%'; } },
    { h: 'Work Exp.', w: W(1, 0), v: function (a) { return dash_(a.workExp); } },
    { h: 'Entrance Exam', w: W(1, 1), v: function (a) { return dash_(a.entranceExam); } },
    { h: 'Other', w: W(2, 2), v: function (a) { return [a.interview, a.other].filter(function (x) { return !blank_(x); }).join(' · ') || '—'; } }
  ], d.acad, 'No academic requirements recorded.');
  cv.spacer(14);

  /* ---- Language requirements ----------------------------------------- */
  drawSectionTitle_(cv, '🗣  LANGUAGE REQUIREMENTS', C.TEAL, null);
  drawModeTable_(cv, [
    { h: 'Test', w: W(2, 2), v: function (l) { return { t: l.test, b: true }; } },
    { h: 'Minimum Score', w: W(2, 2), align: 'center', v: function (l) { return { t: dash_(fmtScore_(l.test, l.minScore)), b: true, fc: C.NAVY, ha: 'center' }; } },
    { h: 'Sub-score', w: W(2, 2), v: function (l) { return dash_(l.subScore); } },
    { h: 'Waiver', w: W(3, 2), v: function (l) { return dash_(l.waiver); } },
    { h: 'Applies To', w: W(3, 2), v: function (l) { return scopeLabel_(l, d.programsById); } }
  ], d.lang, 'No language requirements recorded.');
  cv.spacer(14);

  /* ---- Fees ---------------------------------------------------------- */
  drawSectionTitle_(cv, '💰  FEES', C.AMBER, null);
  var pickFee = function (type) {
    var list = d.fees.filter(function (x) { return x.feeType === type; });
    var wide = list.filter(function (x) { return str_(x.programId).toUpperCase() === 'ALL' || blank_(x.programId); });
    var pool = (wide.length ? wide : list).slice().sort(function (a, b) { return (Number(a.amount) || 0) - (Number(b.amount) || 0); });
    if (!pool.length) return null;
    var f0 = pool[0];
    return { amount: f0.amount, currency: f0.currency, from: pool.length > 1,
      period: [f0.period, (!blank_(f0.degree) && f0.degree !== 'All') ? f0.degree : ''].filter(String).join('  ·  ') };
  };
  var feeCards = [['APPLICATION FEE', pickFee('Application Fee')], ['ADMISSION FEE', pickFee('Admission Fee')],
    ['TUITION FEE', pickFee('Tuition Fee')], ['DEPOSIT', pickFee('Deposit')]];
  var span = cv.mode === 'wide' ? 3 : [3, 2, 3, 2];
  var fr1 = cv.row(20), fr2 = cv.row(30), fr3 = cv.row(18);
  var fc = 1;
  feeCards.forEach(function (x, i) {
    var sp = typeof span === 'number' ? span : span[i];
    cv.put(fr1, fc, sp, '  ' + x[0], { bg: C.AMBER_L, fc: C.AMBER, b: true, fs: 8, va: 'bottom' });
    cv.put(fr2, fc, sp, x[1] ? '  ' + (x[1].from ? 'from ' : '') + fmtNum_(x[1].amount) + ' ' + str_(x[1].currency) : '  —', { bg: C.AMBER_L, fc: C.NAVY, b: true, fs: cv.mode === 'wide' ? 15 : 12 });
    cv.put(fr3, fc, sp, x[1] ? '  ' + dash_(x[1].period) : '  not recorded', { bg: C.AMBER_L, fc: C.MUTED, fs: 8, va: 'top' });
    cv.box(fr1, fc, fr3, fc + sp - 1, C.CARD, 'SOLID_THICK');
    fc += sp;
  });
  cv.spacer(6);
  drawModeTable_(cv, [
    { h: 'Fee Type', w: W(2, 3), v: function (x) { return { t: x.feeType, b: true }; } },
    { h: 'Applies To', w: W(3, 2), v: function (x) { return scopeLabel_(x, d.programsById); } },
    { h: 'Amount', w: W(2, 2), align: 'right', v: function (x) { return { t: fmtNum_(x.amount), b: true, ha: 'right' }; } },
    { h: 'Currency', w: W(1, 1), align: 'center', v: function (x) { return x.currency; } },
    { h: 'Period', w: W(2, 2), v: function (x) { return dash_(x.period); } },
    { h: 'Notes', w: W(2, 0), v: function (x) { return dash_(x.notes); } }
  ], d.fees.sort(function (a, b) { return getList_('FEE_TYPE').indexOf(a.feeType) - getList_('FEE_TYPE').indexOf(b.feeType); }), 'No fees recorded.');
  cv.spacer(14);

  /* ---- Scholarships (+ tiers) ---------------------------------------- */
  drawSectionTitle_(cv, '🏅  SCHOLARSHIPS', C.GREEN, cv.mode === 'wide' ? d.schs.length + ' scholarship(s)' : null);
  var schItems = [];
  d.schs.forEach(function (s) {
    schItems.push({ s: s });
    d.tiers.filter(function (t) { return t.schId === s.id; }).forEach(function (t) { schItems.push({ s: s, t: t }); });
  });
  var tv = function (x, k) { return x.t ? dash_(k === 'ielts' || k === 'minGpa' ? fmtDec_(x.t[k]) : str_(x.t[k])) : '—'; };
  drawModeTable_(cv, [
    { h: 'Scholarship', w: W(2, 3), v: function (x) { return x.t ? { t: '   ↳ ' + x.t.tier, fc: C.GREEN, bg: '#F0FDF4' } : { t: x.s.name + '\n' + [x.s.schType, x.s.mode].filter(String).join(' · '), b: true }; } },
    { h: 'Eligibility / Applies To', w: W(1, 0), v: function (x) { return x.t ? { t: '', bg: '#F0FDF4' } : dash_(x.s.eligibility) + '\n(' + scopeLabel_(x.s, d.programsById) + ')'; } },
    { h: 'GPA', w: W(1, 1), align: 'center', v: function (x) { return x.t ? { t: tv(x, 'minGpa'), bg: '#F0FDF4', ha: 'center' } : dash_(fmtDec_(x.s.minGpa)); } },
    { h: 'IELTS', w: W(1, 1), align: 'center', v: function (x) { return x.t ? { t: tv(x, 'ielts'), bg: '#F0FDF4', ha: 'center' } : '—'; } },
    { h: 'TOEFL', w: W(1, 1), align: 'center', v: function (x) { return x.t ? { t: tv(x, 'toefl'), bg: '#F0FDF4', ha: 'center' } : '—'; } },
    { h: 'PTE', w: W(1, 0), align: 'center', v: function (x) { return x.t ? { t: tv(x, 'pte'), bg: '#F0FDF4', ha: 'center' } : '—'; } },
    { h: 'TOPIK', w: W(1, 1), align: 'center', v: function (x) { return x.t ? { t: tv(x, 'topik'), bg: '#F0FDF4', ha: 'center' } : '—'; } },
    { h: 'Scholarship %', w: W(1, 1), align: 'center', v: function (x) { var p = x.t ? x.t.percent : x.s.percent; return { t: blank_(p) ? '—' : p + '%', b: true, fc: C.GREEN, ha: 'center', bg: x.t ? '#F0FDF4' : null }; } },
    { h: 'Amount', w: W(1, 2), v: function (x) { var o = x.t || x.s; return { t: blank_(o.amount) ? '—' : fmtNum_(o.amount) + ' ' + str_(o.currency || x.s.currency), bg: x.t ? '#F0FDF4' : null }; } },
    { h: 'Duration', w: W(1, 0), v: function (x) { return x.t ? { t: '', bg: '#F0FDF4' } : dash_(x.s.duration); } },
    { h: 'Renewal Requirement', w: W(1, 0), v: function (x) { return x.t ? { t: dash_(str_(x.t.otherTest) ? x.t.otherTest + ' ' + str_(x.t.otherScore) : ''), bg: '#F0FDF4' } : dash_(x.s.renewal); } }
  ], schItems, 'No scholarships recorded.');
  if (cv.mode === 'print' && d.schs.length) {
    d.schs.forEach(function (s) {
      if (blank_(s.eligibility) && blank_(s.renewal) && blank_(s.duration)) return;
      var r = cv.row(20);
      cv.put(r, 1, cv.n, '  ' + s.name + ':  ' + [s.eligibility ? 'Eligibility — ' + s.eligibility : '', s.duration ? 'Duration — ' + s.duration : '', s.renewal ? 'Renewal — ' + s.renewal : ''].filter(String).join('   ·   '),
        { bg: C.CARD, fc: C.MUTED, fs: 8, wrap: true });
    });
  }
  cv.spacer(14);

  /* ---- Documents checklist ------------------------------------------- */
  drawSectionTitle_(cv, '📄  DOCUMENT CHECKLIST', C.BLUE, cv.mode === 'wide' ? '☑ mandatory   ◐ conditional   ☐ optional' : null);
  var icon = { 'Mandatory': '☑', 'Conditional': '◐', 'Optional': '☐' };
  var lvlStyle = { 'Mandatory': [C.ROSE_L, C.ROSE], 'Conditional': [C.AMBER_L, C.AMBER], 'Optional': ['#F1F5F9', C.MUTED] };
  drawModeTable_(cv, [
    { h: 'Document', w: W(3, 3), v: function (x) { return { t: (icon[x.level] || '☑') + '  ' + x.document, b: true }; } },
    { h: 'Requirement', w: W(2, 2), align: 'center', v: function (x) { var s = lvlStyle[x.level] || lvlStyle.Mandatory; return { t: dash_(x.level), bg: s[0], fc: s[1], b: true, ha: 'center' }; } },
    { h: 'Specification', w: W(4, 3), v: function (x) { return dash_(x.spec); } },
    { h: 'Applies To', w: W(3, 2), v: function (x) { return scopeLabel_(x, d.programsById); } }
  ], d.docs.sort(function (a, b) { return getList_('DOC_REQ').indexOf(a.level) - getList_('DOC_REQ').indexOf(b.level); }), 'No document requirements recorded.');
  cv.spacer(14);

  /* ---- Country-specific requirements --------------------------------- */
  drawSectionTitle_(cv, '🌐  ' + str_(u.country).toUpperCase() + ' — COUNTRY-SPECIFIC REQUIREMENTS', C.PURPLE, null);
  drawModeTable_(cv, [
    { h: 'Category', w: W(2, 2), v: function (x) { return { t: x.category, b: true }; } },
    { h: 'Requirement', w: W(3, 3), v: function (x) { return { t: x.requirement, b: true, fc: C.NAVY }; } },
    { h: 'Details', w: W(4, 3), v: function (x) { return dash_(x.details); } },
    { h: 'Mandatory', w: W(1, 1), align: 'center', v: function (x) { return { t: dash_(x.mandatory), ha: 'center', fc: x.mandatory === 'Yes' ? C.ROSE : C.MUTED, b: true }; } },
    { h: 'Scope', w: W(2, 1), v: function (x) { return blank_(x.uniId) || str_(x.uniId).toUpperCase() === 'ALL' ? 'All in ' + x.country : 'This university'; } }
  ], d.csr, 'No country-specific requirements recorded for ' + u.country + '.');
  cv.spacer(14);

  /* ---- Visa ---------------------------------------------------------- */
  if (d.visa.length) {
    drawSectionTitle_(cv, '🛂  STUDENT VISA — ' + str_(u.country).toUpperCase(), C.NAVY2, null);
    drawModeTable_(cv, [
      { h: 'Visa Type', w: W(2, 2), v: function (x) { return { t: x.visaType, b: true }; } },
      { h: 'Financial Proof', w: W(3, 3), v: function (x) { return dash_(x.financial); } },
      { h: 'Processing Time', w: W(2, 1), v: function (x) { return dash_(x.processing); } },
      { h: 'Work Rights', w: W(2, 2), v: function (x) { return dash_(x.workRights); } },
      { h: 'Official Source', w: W(3, 2), v: function (x) { return blank_(x.url) ? '—' : { t: x.url, link: x.url }; } }
    ], d.visa, '');
    cv.spacer(14);
  }

  /* ---- Sources --------------------------------------------------------- */
  drawSectionTitle_(cv, '🔗  OFFICIAL SOURCES', C.NAVY2, null);
  var srcItems = [];
  if (!blank_(u.website)) srcItems.push({ sourceType: 'Official Website', title: u.name, url: u.website, checkedOn: u.lastChecked, checkedBy: u.updatedBy });
  if (!blank_(u.intlWebsite)) srcItems.push({ sourceType: 'International Website', title: 'International admissions', url: u.intlWebsite, checkedOn: u.lastChecked, checkedBy: u.updatedBy });
  srcItems = srcItems.concat(d.sources);
  drawModeTable_(cv, [
    { h: 'Source Type', w: W(2, 2), v: function (x) { return { t: x.sourceType, b: true }; } },
    { h: 'Title', w: W(3, 2), v: function (x) { return dash_(x.title); } },
    { h: 'URL', w: W(4, 4), v: function (x) { return blank_(x.url) ? '—' : { t: x.url, link: /^https?:/i.test(x.url) ? x.url : null }; } },
    { h: 'Last Checked', w: W(1, 1), align: 'center', v: function (x) { return dash_(fmtDate_(x.checkedOn)); } },
    { h: 'Checked By', w: W(2, 1), v: function (x) { return dash_(x.checkedBy); } }
  ], srcItems, 'No sources recorded.');
  cv.spacer(10);
  var ft = cv.row(22);
  var flag = sampleBadge_(u) ? '   ⚠ ' + UMS.SAMPLE_FLAG + ' — not real admission information.' : '';
  cv.put(ft, 1, cv.n, 'Last updated: ' + (u.updated instanceof Date ? fmtDate_(u.updated) : dash_(u.updated)) + '   ·   Status: ' + dash_(u.status) +
    '   ·   Always confirm details on the official university website before advising students.' + flag,
    { fc: sampleBadge_(u) ? C.PURPLE : C.MUTED, fs: 8, i: true, wrap: true });
}

/* ============================================================================
 *  UNIVERSITY SEARCH
 * ========================================================================== */
function renderUniversitySearch_() {
  var sh = getSheet_(UMS.SHEETS.US);
  var L = controlLayout_(UMS.SHEETS.US);
  var f = readControls_(UMS.SHEETS.US);
  var cv = new Canvas('wide');
  var u = universityByName_(f.university, f.country);
  if (u) {
    var d = gatherUniversity_(u, f);
    drawProfileSections_(cv, d, { degree: f.degree });
  } else {
    drawDirectory_(cv, f.country, !blank_(f.university));
  }
  clearRegion_(sh, L.resultsRow);
  cv.flush(sh, L.resultsRow);
  finishPage_(sh, L.resultsRow + cv.rowCount() - 1, 'wide');
}

/** Shown when no university is selected: a directory of universities. */
function drawDirectory_(cv, country, notFound) {
  var C = UMS.C, S = UMS.SHEETS;
  if (notFound) {
    var w = cv.row(30);
    cv.put(w, 1, cv.n, '⚠  University not found in the selected country. Choose one from the dropdown.', { bg: C.AMBER_L, fc: C.AMBER, b: true });
    cv.spacer(10);
  }
  var unis = readTable_(S.UNIVERSITIES).objs.filter(function (x) { return isActive_(x) && (blank_(country) || norm_(x.country) === norm_(country)); })
    .sort(function (a, b) { return (str_(a.country) + str_(a.name)).localeCompare(str_(b.country) + str_(b.name)); });
  var progCount = {}, nextDl = {};
  readTable_(S.PROGRAMS).objs.forEach(function (p) { if (isActive_(p)) progCount[p.uniId] = (progCount[p.uniId] || 0) + 1; });
  readTable_(S.ROUNDS).objs.forEach(function (r) {
    if (!isActive_(r) || !(r.appEnd instanceof Date) || daysUntil_(r.appEnd) < 0) return;
    if (!nextDl[r.uniId] || r.appEnd < nextDl[r.uniId]) nextDl[r.uniId] = r.appEnd;
  });
  drawSectionTitle_(cv, '🏛  UNIVERSITY DIRECTORY' + (blank_(country) ? '  —  ALL COUNTRIES' : '  —  ' + String(country).toUpperCase()), C.BLUE,
    unis.length + ' universit' + (unis.length === 1 ? 'y' : 'ies') + '  ·  select one above for full details');
  drawTable_(cv, [{ h: 'University', span: 3 }, { h: 'Country', span: 2 }, { h: 'City', span: 1 }, { h: 'Type', span: 1 },
    { h: 'Programs', span: 1, align: 'center' }, { h: 'Next Deadline', span: 2, align: 'center' }, { h: 'Status', span: 2, align: 'center' }],
    unis.slice(0, 500).map(function (x) {
      var dl = nextDl[x.id], ds = dl ? deadlineStyle_(dl) : null;
      return [{ t: x.name, b: true, fc: C.NAVY }, x.country, dash_(x.city), dash_(x.type), { t: String(progCount[x.id] || 0), ha: 'center' },
        dl ? { t: fmtDate_(dl) + '  (' + ds.label + ')', bg: ds.bg, fc: ds.fc, ha: 'center', b: true } : { t: '—', ha: 'center' }, statusCell_(x.status)];
    }), { empty: 'No universities yet. Go to DATA ENTRY → Add University.' });
}

/* ============================================================================
 *  PROGRAM SEARCH
 * ========================================================================== */
function searchPrograms_(f) {
  var S = UMS.SHEETS;
  var cn = norm_(f.country), un = norm_(f.university), major = norm_(f.major);
  var unis = {};
  readTable_(S.UNIVERSITIES).objs.forEach(function (u) { unis[u.id] = u; });
  var fees = readTable_(S.FEES).objs.filter(isActive_);
  var lang = readTable_(S.LANGUAGE).objs.filter(isActive_);
  var schs = readTable_(S.SCHOLARSHIPS).objs.filter(isActive_);
  var acad = readTable_(S.ACADEMIC).objs.filter(isActive_);
  var intakes = readTable_(S.INTAKES).objs.filter(isActive_);
  var rounds = readTable_(S.ROUNDS).objs.filter(isActive_);
  var byUni = function (arr) { var m = {}; arr.forEach(function (x) { (m[x.uniId] = m[x.uniId] || []).push(x); }); return m; };
  var feesU = byUni(fees), langU = byUni(lang), schU = byUni(schs), acadU = byUni(acad), intU = byUni(intakes), rndU = byUni(rounds);

  var scores = {};
  if (!blank_(f.ielts)) scores['IELTS'] = toNum_(f.ielts, 'IELTS', 0, 9);
  if (!blank_(f.toefl)) scores['TOEFL iBT'] = toNum_(f.toefl, 'TOEFL iBT', 0, 120);
  if (!blank_(f.pte)) scores['PTE Academic'] = toNum_(f.pte, 'PTE', 10, 90);
  if (!blank_(f.topik)) scores['TOPIK'] = toNum_(f.topik, 'TOPIK', 1, 6);
  var hasScores = Object.keys(scores).length > 0;
  var gpa = blank_(f.gpa) ? null : toNum_(f.gpa, 'GPA', 0, 100);
  var tMin = blank_(f.tuitionMin) ? null : toNum_(f.tuitionMin, 'Tuition From', 0);
  var tMax = blank_(f.tuitionMax) ? null : toNum_(f.tuitionMax, 'Tuition To', 0);
  var schWanted = str_(f.scholarship);

  var out = [];
  readTable_(S.PROGRAMS).objs.forEach(function (p) {
    if (!isActive_(p)) return;
    var u = unis[p.uniId];
    if (!u || !isActive_(u)) return;
    if (cn && norm_(p.country) !== cn) return;
    if (un && norm_(p.university) !== un) return;
    if (!blank_(f.degree) && p.degree !== f.degree) return;
    if (!blank_(f.language) && p.language !== f.language) return;
    if (major) {
      var hay = norm_([p.program, p.major, p.department, p.faculty].join(' '));
      if (hay.indexOf(major) < 0) return;
    }
    var uIntakes = (intU[p.uniId] || []).filter(function (i) { return blank_(i.degree) || i.degree === 'All' || i.degree === p.degree; });
    if (!blank_(f.intake)) {
      var t = norm_(f.intake), txt = norm_(p.intakes);
      var ok = txt ? txt.indexOf(t) >= 0 : uIntakes.some(function (i) { return norm_(i.term) === t || norm_(i.intake).indexOf(t) >= 0; });
      if (!ok) return;
    }
    // language
    var myLang = preferSpecific_(langU[p.uniId] || [], p);
    var langOk = true, langText = myLang.map(function (l) { return l.test + ' ' + fmtScore_(l.test, l.minScore); }).join(' · ');
    if (hasScores) {
      if (myLang.length) {
        langOk = myLang.some(function (l) {
          if (scores[l.test] === undefined) return false;
          var m = Number(l.minScore);
          return isNaN(m) ? true : scores[l.test] >= m;
        });
      }
      if (!langOk) return;
    }
    // academics
    var myAcad = preferSpecific_(acadU[p.uniId] || [], p)[0] || null;
    if (gpa !== null && myAcad && !blank_(myAcad.minGpa) && Number(myAcad.minGpa) > gpa) return;
    // tuition
    var myFees = preferSpecific_(feesU[p.uniId] || [], p);
    var tu = first_(myFees, function (x) { return x.feeType === 'Tuition Fee'; });
    if (!blank_(f.currency) && (!tu || tu.currency !== f.currency)) return;
    if ((tMin !== null || tMax !== null)) {
      if (!tu || blank_(tu.amount)) return;
      var amt = Number(tu.amount);
      if (tMin !== null && amt < tMin) return;
      if (tMax !== null && amt > tMax) return;
    }
    // scholarships
    var mySch = (schU[p.uniId] || []).filter(function (s) { return appliesTo_(s, p); });
    if (schWanted === 'Yes' && !mySch.length) return;
    if (schWanted === 'No' && mySch.length) return;
    // next deadline
    var intIds = {};
    uIntakes.forEach(function (i) { intIds[i.id] = true; });
    var next = null;
    (rndU[p.uniId] || []).forEach(function (r) {
      if (!intIds[r.intakeId] || !(r.appEnd instanceof Date) || daysUntil_(r.appEnd) < 0) return;
      if (!next || r.appEnd < next) next = r.appEnd;
    });
    out.push({ p: p, u: u, tuition: tu, schs: mySch, langText: langText, acad: myAcad, next: next,
      intakeText: str_(p.intakes) || uniq_(uIntakes.map(function (i) { return str_(i.intake); })).join(', ') });
  });

  var sort = str_(f.sort) || 'Country';
  var tuitionOf = function (x) { return x.tuition && !blank_(x.tuition.amount) ? Number(x.tuition.amount) : null; };
  out.sort(function (a, b) {
    switch (sort) {
      case 'University': return (str_(a.u.name) + a.p.program).localeCompare(str_(b.u.name) + b.p.program);
      case 'Program': return str_(a.p.program).localeCompare(str_(b.p.program));
      case 'Tuition (low → high)': { var x = tuitionOf(a), y = tuitionOf(b); return (x === null ? 9e18 : x) - (y === null ? 9e18 : y); }
      case 'Tuition (high → low)': { var x2 = tuitionOf(a), y2 = tuitionOf(b); return (y2 === null ? -1 : y2) - (x2 === null ? -1 : x2); }
      case 'Nearest Deadline': return (a.next ? a.next.getTime() : 9e15) - (b.next ? b.next.getTime() : 9e15);
      default: return (str_(a.p.country) + str_(a.u.name) + a.p.program).localeCompare(str_(b.p.country) + str_(b.u.name) + b.p.program);
    }
  });
  return out;
}

function renderProgramSearch_() {
  var sh = getSheet_(UMS.SHEETS.PS);
  var L = controlLayout_(UMS.SHEETS.PS);
  var f = readControls_(UMS.SHEETS.PS);
  var C = UMS.C;
  var results = searchPrograms_(f);
  var max = Number(setting_('Program Search Max Results')) || 500;
  var cv = new Canvas('wide');
  var active = [];
  var labels = { country: 'Country', university: 'University', degree: 'Degree', major: 'Major', intake: 'Intake', language: 'Language',
    ielts: 'IELTS', toefl: 'TOEFL', pte: 'PTE', topik: 'TOPIK', gpa: 'GPA', scholarship: 'Scholarship', currency: 'Currency', tuitionMin: 'Tuition ≥', tuitionMax: 'Tuition ≤' };
  for (var k in labels) if (!blank_(f[k]) && !(k === 'scholarship' && f[k] === 'Any')) active.push(labels[k] + ': ' + f[k]);
  var sr = cv.row(30);
  cv.put(sr, 1, 4, (results.length ? '✔  ' : '✕  ') + results.length + ' program' + (results.length === 1 ? '' : 's') + ' found',
    { bg: results.length ? C.GREEN_L : C.ROSE_L, fc: results.length ? C.GREEN : C.ROSE, b: true, fs: 12 });
  cv.put(sr, 5, 8, active.length ? '  Filters →  ' + active.join('   ·   ') : '  No filters — showing all programs', { bg: C.CARD, fc: C.MUTED, fs: 9, wrap: true });
  cv.box(sr, 1, sr, 12, C.LINE, 'SOLID');
  cv.spacer(8);
  if (results.length > max) {
    var wr = cv.row(24);
    cv.put(wr, 1, 12, '⚠ Showing the first ' + max + ' results. Add more filters to narrow down (limit set in 01_Settings).', { bg: C.AMBER_L, fc: C.AMBER, b: true, fs: 9 });
  }
  drawTable_(cv, [{ h: 'Country', span: 1 }, { h: 'University', span: 2 }, { h: 'Program', span: 2 }, { h: 'Degree', span: 1 },
    { h: 'Intake / Next Deadline', span: 1 }, { h: 'Language', span: 1, align: 'center' }, { h: 'Tuition', span: 1 },
    { h: 'Scholarship', span: 1, align: 'center' }, { h: 'Min. Requirements', span: 2 }],
    results.slice(0, max).map(function (x) {
      var ds = x.next ? deadlineStyle_(x.next) : null;
      var req = [x.langText, x.acad && !blank_(x.acad.minGpa) ? 'GPA ' + fmtDec_(x.acad.minGpa) : ''].filter(String).join(' · ');
      return [x.p.country, { t: x.u.name + (sampleBadge_(x.p) ? '\n⚠ SAMPLE' : ''), b: true, fc: C.NAVY },
        { t: programTitle_(x.p), b: true }, x.p.degree,
        { t: dash_(x.intakeText) + (x.next ? '\n⏰ ' + fmtDate_(x.next) : ''), bg: ds && ds.bg ? ds.bg : null },
        { t: dash_(x.p.language), ha: 'center' },
        x.tuition ? fmtMoney_(x.tuition.amount, x.tuition.currency, x.tuition.period) : '—',
        x.schs.length ? { t: '✔ ' + x.schs.length, fc: C.GREEN, b: true, ha: 'center' } : { t: '—', ha: 'center', fc: C.MUTED },
        { t: dash_(req), fs: 8 }];
    }), { empty: 'No programs match these filters. Try removing a filter.' });
  clearRegion_(sh, L.resultsRow);
  cv.flush(sh, L.resultsRow);
  finishPage_(sh, L.resultsRow + cv.rowCount() - 1, 'wide');
}

/* ============================================================================
 *  PRINT VIEW
 * ========================================================================== */
function renderPrintView_() {
  var sh = getSheet_(UMS.SHEETS.PV);
  var L = controlLayout_(UMS.SHEETS.PV);
  var f = readControls_(UMS.SHEETS.PV);
  var C = UMS.C;
  var cv = new Canvas('print');
  var u = universityByName_(f.university, f.country);
  if (!u) {
    var r = cv.row(40);
    cv.put(r, 1, cv.n, 'Select a Country and University above, then press GENERATE.', { bg: C.CARD, fc: C.MUTED, i: true, ha: 'center' });
    cv.box(r, 1, r, cv.n, C.LINE);
  } else {
    var h = cv.row(26);
    cv.put(h, 1, 6, 'UNIVERSITY PROFILE', { bg: C.NAVY, fc: C.WHITE, b: true, fs: 13 });
    cv.put(h, 7, 4, str_(setting_('Consultancy Name')) + '  ·  ' + fmtDate_(new Date()), { bg: C.NAVY, fc: C.SUB, fs: 8, ha: 'right' });
    cv.spacer(8);
    drawProfileSections_(cv, gatherUniversity_(u, {}), {});
  }
  clearRegion_(sh, L.resultsRow);
  cv.flush(sh, L.resultsRow);
  finishPage_(sh, L.resultsRow + cv.rowCount() - 1, 'print');
}


/* ============================================================================
 *  UNIVERSITY DATA ENTRY — form layout
 *  field: [key, label, validationSpec, {full, ro, req, def}]
 * ========================================================================== */

var DE_CARDS = [
  { id: 'ctx', title: '①  SELECT RECORD  —  context for adding & updating', color: '#1D4ED8',
    hint: 'Choose where new data belongs, or which record to LOAD',
    fields: [
      ['ctxCountry', 'Country', { helper: 'COUNTRIES' }, { req: true }], ['ctxUniversity', 'University', { helper: 'DE_UNIV' }, { req: true }],
      ['ctxDegree', 'Degree Level', { list: 'DEGREE' }], ['ctxProgram', 'Program', { helper: 'DE_PROGRAM' }],
      ['ctxIntake', 'Intake', { helper: 'DE_INTAKE' }], ['ctxRound', 'Application Round', { helper: 'DE_ROUND' }],
      ['metaStatus', 'Record Status', { list: 'STATUS' }, { def: 'Pending Verification' }], ['metaChecked', 'Last Checked', { date: true, nf: 'yyyy-mm-dd' }],
      ['metaSource', 'Source URL (applies to new records)', { url: true }, { full: true }]
    ],
    buttons: [['⤓  LOAD RECORD', 'light', 'btnLoadRecord'], ['✔  UPDATE RECORD', 'accent', 'updateRecord'], ['✕  CLEAR FORM', 'danger', 'clearForm']] },

  { id: 'country', title: 'CARD 0 — COUNTRY & STUDENT VISA', color: '#475569',
    hint: 'New countries appear in every dashboard automatically',
    fields: [
      ['coName', 'Country Name', null, { req: true }], ['coCode', 'Country Code (auto if blank)', null],
      ['coRegion', 'Region', { list: 'REGION' }], ['coCurrency', 'Default Currency', { list: 'CURRENCY' }],
      ['coLanguage', 'Official Language(s)', null], ['coActive', 'Active', { list: 'YESNO' }, { def: 'Yes' }],
      ['viType', 'Visa Type', null], ['viProcessing', 'Visa Processing Time', null],
      ['viFinancial', 'Financial Proof', null, { full: true }],
      ['viWork', 'Work Rights', null], ['viUrl', 'Official Visa URL', { url: true }]
    ],
    buttons: [['＋  ADD COUNTRY', 'primary', 'addCountry'], ['＋  ADD VISA INFO', 'light', 'addVisaInformation']] },

  { id: 'uni', title: 'CARD 1 — UNIVERSITY INFORMATION', color: '#1D4ED8',
    hint: 'Duplicate check: Country + University',
    fields: [
      ['uniCountry', 'Country', { helper: 'COUNTRIES' }, { req: true }], ['uniName', 'University Name', null, { req: true }],
      ['uniCity', 'City', null], ['uniCampus', 'Campus', null],
      ['uniType', 'University Type', { list: 'UNI_TYPE' }], ['uniRanking', 'Ranking', null],
      ['uniWebsite', 'Official Website', { url: true }], ['uniIntl', 'International Website', { url: true }],
      ['uniLogo', 'Logo (image URL)', { url: true }], ['uniAccreditation', 'Accreditation', null],
      ['uniAddress', 'Campus Address', null, { full: true }],
      ['uniNotes', 'Notes', null, { full: true }],
      ['uniId', 'University ID', null, { ro: true }], ['uniCampusId', 'Campus ID', null, { ro: true }]
    ],
    buttons: [['＋  ADD UNIVERSITY', 'primary', 'addUniversity'], ['＋  ADD CAMPUS', 'light', 'addCampus']] },

  { id: 'intake', title: 'CARD 2 — INTAKE & APPLICATION', color: '#BE123C',
    hint: 'Uses the University in ①. Unlimited intakes and rounds.',
    fields: [
      ['inTerm', 'Intake', { list: 'INTAKE_TERM' }, { req: true }], ['inYear', 'Intake Year', { number: [2000, 2100] }, { req: true }],
      ['inType', 'Intake Type', { list: 'INTAKE_TYPE' }], ['inDegree', 'Applies To Degree (blank = all)', { list: 'DEGREE' }],
      ['inCampus', 'Campus', { helper: 'DE_CAMPUS' }], ['inRolling', 'Rolling Admission', { list: 'YESNO' }],
      ['inRound', 'Application Round', { list: 'ROUND' }], ['inRoundCustom', 'Custom Round / Intake Label', null],
      ['inAppStart', 'Application Start', { date: true, nf: 'yyyy-mm-dd' }], ['inAppEnd', 'Application End', { date: true, nf: 'yyyy-mm-dd' }],
      ['inIntStart', 'Interview Start', { date: true, nf: 'yyyy-mm-dd' }], ['inIntEnd', 'Interview End', { date: true, nf: 'yyyy-mm-dd' }],
      ['inResult', 'Result Date', { date: true, nf: 'yyyy-mm-dd' }], ['inDeposit', 'Deposit Deadline', { date: true, nf: 'yyyy-mm-dd' }],
      ['inEnroll', 'Enrollment Deadline', { date: true, nf: 'yyyy-mm-dd' }], ['inNotes', 'Notes', null],
      ['inIntakeId', 'Intake ID', null, { ro: true }], ['inRoundId', 'Round ID', null, { ro: true }]
    ],
    buttons: [['＋  ADD INTAKE', 'primary', 'addIntake'], ['＋  ADD APPLICATION ROUND', 'light', 'addApplicationRound']] },

  { id: 'program', title: 'CARD 3 — PROGRAM', color: '#0F766E',
    hint: 'Duplicate check: University + Degree + Program',
    fields: [
      ['prDegree', 'Degree Level', { list: 'DEGREE' }, { req: true }], ['prName', 'Program Name', null, { req: true }],
      ['prFaculty', 'Faculty', null], ['prDepartment', 'Department', null],
      ['prMajor', 'Major / Field', null], ['prDuration', 'Duration (e.g. 2 years)', null],
      ['prMode', 'Study Mode', { list: 'STUDY_MODE' }], ['prLanguage', 'Language of Instruction', { list: 'LANG_INSTR' }],
      ['prCampus', 'Campus', { helper: 'DE_CAMPUS' }], ['prIntakes', 'Intakes Offered (e.g. Spring; Fall)', null],
      ['prNotes', 'Notes', null, { full: true }],
      ['prId', 'Program ID', null, { ro: true }]
    ],
    buttons: [['＋  ADD PROGRAM', 'primary', 'addProgram']] },

  { id: 'acad', title: 'CARD 4 — ACADEMIC REQUIREMENTS', color: '#163A5F',
    hint: 'For the Program in ① — or all programs of the Degree if Program is blank',
    fields: [
      ['acMinGpa', 'Minimum GPA', { number: [0, 100] }], ['acScale', 'GPA Scale (e.g. 4.0 / 4.5 / 100)', null],
      ['acPrev', 'Previous Degree Required', null], ['acBackground', 'Required Background', null],
      ['acPercent', 'Minimum Percentage', { number: [0, 100] }], ['acWork', 'Work Experience', null],
      ['acExam', 'Entrance Exam (GRE/GMAT/SAT)', null], ['acInterview', 'Portfolio / Interview', null],
      ['acOther', 'Other Requirements', null, { full: true }],
      ['acId', 'Requirement ID', null, { ro: true }]
    ],
    buttons: [['＋  ADD ACADEMIC REQUIREMENT', 'primary', 'addAcademicRequirement']] },

  { id: 'lang', title: 'CARD 5 — LANGUAGE REQUIREMENTS', color: '#0F766E',
    hint: 'One row per test · for the Program in ① or all programs of the Degree',
    grid: { key: 'langGrid', rows: 6, cols: [
      ['test', 'Test', 2, { list: 'LANG_TEST' }], ['minScore', 'Minimum Score', 2, null], ['subScore', 'Sub-score Requirement', 3, null],
      ['waiver', 'Waiver Condition', 3, null], ['id', 'Record ID', 2, null, true]] },
    buttons: [['＋  ADD LANGUAGE REQUIREMENT', 'primary', 'addLanguageRequirement']] },

  { id: 'fees', title: 'CARD 6 — FEES', color: '#B45309',
    hint: 'Fill Amount on the rows you need · tuition may be per semester, year, credit …',
    grid: { key: 'feeGrid', rows: 8, prefill: { feeType: ['Application Fee', 'Admission Fee', 'Tuition Fee', 'Registration Fee', 'Student Service Fee', 'Insurance', 'Deposit', 'Other'] },
      cols: [['feeType', 'Fee Type', 2, { list: 'FEE_TYPE' }], ['amount', 'Amount', 2, { number: [0, 1e12] }], ['currency', 'Currency', 2, { list: 'CURRENCY' }],
        ['period', 'Period', 2, { list: 'PERIOD' }], ['notes', 'Notes', 2, null], ['id', 'Record ID', 2, null, true]] },
    buttons: [['＋  ADD FEE', 'primary', 'addFee']] },

  { id: 'sch', title: 'CARD 7 — SCHOLARSHIP', color: '#15803D',
    hint: 'Duplicate check: University + Scholarship + Program',
    fields: [
      ['scName', 'Scholarship Name', null, { req: true }], ['scType', 'Scholarship Type', { list: 'SCH_TYPE' }],
      ['scMode', 'Award Mode', { list: 'SCH_MODE' }], ['scMinGpa', 'Minimum GPA', { number: [0, 100] }],
      ['scPercent', 'Scholarship %', { number: [0, 100] }], ['scAmount', 'Amount', { number: [0, 1e12] }],
      ['scCurrency', 'Currency', { list: 'CURRENCY' }], ['scDuration', 'Duration', null],
      ['scEligibility', 'Eligibility', null, { full: true }],
      ['scRenewal', 'Renewal Requirement', null, { full: true }],
      ['scExisting', 'Existing Scholarship (for tiers)', { helper: 'DE_SCH' }], ['scId', 'Scholarship ID', null, { ro: true }]
    ],
    grid: { key: 'tierGrid', rows: 5, title: 'SCORE-BASED TIERS  (optional)', cols: [
      ['tier', 'Tier Name', 2, null], ['minGpa', 'Min GPA', 1, { number: [0, 100] }], ['ielts', 'IELTS', 1, { number: [0, 9] }],
      ['toefl', 'TOEFL iBT', 1, { number: [0, 120] }], ['pte', 'PTE', 1, { number: [10, 90] }], ['topik', 'TOPIK', 1, { number: [1, 6] }],
      ['otherTest', 'Other Test', 1, null], ['otherScore', 'Other Score', 1, null], ['percent', 'Schol. %', 1, { number: [0, 100] }],
      ['amount', 'Amount', 1, { number: [0, 1e12] }], ['id', 'Tier ID', 1, null, true]] },
    buttons: [['＋  ADD SCHOLARSHIP', 'primary', 'addScholarship'], ['＋  ADD SCHOLARSHIP TIER', 'light', 'addScholarshipTier']] },

  { id: 'docs', title: 'CARD 8 — DOCUMENT REQUIREMENTS', color: '#1D4ED8',
    hint: 'One row per document',
    grid: { key: 'docGrid', rows: 8, cols: [
      ['document', 'Document', 3, { list: 'DOC', loose: true }], ['level', 'Requirement', 2, { list: 'DOC_REQ' }],
      ['spec', 'Specification', 5, null], ['id', 'Record ID', 2, null, true]] },
    buttons: [['＋  ADD DOCUMENT', 'primary', 'addDocumentRequirement']] },

  { id: 'csr', title: 'CARD 9 — COUNTRY-SPECIFIC REQUIREMENTS', color: '#6D28D9',
    hint: 'Scope "Country" = every university in the country in ①',
    grid: { key: 'csrGrid', rows: 5, cols: [
      ['category', 'Category', 2, { list: 'CSR_CAT' }], ['requirement', 'Requirement', 3, null], ['details', 'Details', 3, null],
      ['mandatory', 'Mandatory', 1, { list: 'YESNO' }], ['scope', 'Scope', 1, { list: 'SCOPE' }], ['id', 'Record ID', 2, null, true]] },
    buttons: [['＋  ADD COUNTRY REQUIREMENT', 'primary', 'addCountrySpecificRequirement']] },

  { id: 'src', title: 'CARD 10 — SOURCES', color: '#163A5F',
    hint: 'Where the information was verified',
    grid: { key: 'srcGrid', rows: 5, cols: [
      ['sourceType', 'Source Type', 2, { list: 'SOURCE_TYPE' }], ['title', 'Title', 2, null], ['url', 'URL', 4, { url: true }],
      ['checkedOn', 'Last Checked', 1, { date: true, nf: 'yyyy-mm-dd' }], ['checkedBy', 'Checked By', 1, null], ['id', 'Record ID', 2, null, true]] },
    buttons: [['＋  ADD SOURCE', 'primary', 'addSource']] }
];

function buildDataEntry_() {
  var C = UMS.C;
  var cv = new Canvas('wide');
  drawHeader_(cv, 'UNIVERSITY DATA ENTRY', 'Add or update university information — no need to open the database', UMS.SHEETS.DE);
  var fields = {}, grids = {}, defaults = {}, prefill = [];

  var info = cv.row(40);
  cv.put(info, 1, 12, '  ⓘ  ADD: fill a card and press its ＋ button.      UPDATE: choose the record in ① → LOAD RECORD → edit → UPDATE RECORD.\n' +
    '      Fields marked * are required. IDs, dates and "Updated By" are filled automatically. Every change is written to the Audit Log.',
    { bg: C.BLUE_XL, fc: C.NAVY, fs: 9, wrap: true });
  cv.box(info, 1, info, 12, C.INPUT_BORDER);
  cv.spacer(14);

  DE_CARDS.forEach(function (card) {
    drawSectionTitle_(cv, card.title, card.color, card.hint);
    var top = cv.row(8); cv.fill(top, 1, 12, C.CARD);
    // fields — two per row, or one full-width
    var pending = null;
    var placeField = function (f, r, c, inputSpan) {
      var o = f[3] || {};
      cv.put(r, c, 2, '  ' + f[1] + (o.req ? ' *' : ''), { bg: C.CARD, fc: o.req ? C.NAVY : C.LABEL, b: true, fs: 9, wrap: true });
      cv.put(r, c + 2, inputSpan, '', { bg: o.ro ? C.RO : C.INPUT, fc: o.ro ? C.MUTED : C.TEXT, i: !!o.ro, fs: 10 });
      cv.box(r, c + 2, r, c + 1 + inputSpan, o.ro ? C.LINE_L : C.INPUT_BORDER);
      if (f[2]) cv.validate(r, c + 2, f[2]);
      fields[f[0]] = [r, c + 2];
      if (o.def !== undefined) defaults[f[0]] = o.def;
    };
    (card.fields || []).forEach(function (f) {
      var o = f[3] || {};
      if (o.full) {
        var r = cv.row(28); cv.fill(r, 1, 12, C.CARD);
        placeField(f, r, 1, 10);
        pending = null;
      } else if (pending === null) {
        pending = cv.row(28); cv.fill(pending, 1, 12, C.CARD);
        placeField(f, pending, 1, 4);
      } else {
        placeField(f, pending, 7, 4);
        pending = null;
      }
    });
    if (card.grid) {
      var g = card.grid;
      if (card.fields) { var gs = cv.row(10); cv.fill(gs, 1, 12, C.CARD); }
      if (g.title) {
        var gt = cv.row(22); cv.fill(gt, 1, 12, C.CARD);
        cv.put(gt, 1, 12, '  ' + g.title, { bg: C.CARD, fc: card.color, b: true, fs: 9 });
      }
      var hr = cv.row(24);
      var cc = 1, colPos = {};
      g.cols.forEach(function (col) {
        cv.put(hr, cc, col[2], col[1], { bg: C.HEAD_BG, fc: C.LABEL, b: true, fs: 9 });
        colPos[col[0]] = cc; cc += col[2];
      });
      var r0 = null;
      for (var i = 0; i < g.rows; i++) {
        var gr = cv.row(26);
        if (r0 === null) r0 = gr;
        var c2 = 1;
        g.cols.forEach(function (col) {
          var ro = !!col[4];
          cv.put(gr, c2, col[2], '', { bg: ro ? C.RO : C.INPUT, fc: ro ? C.MUTED : C.TEXT, i: ro, fs: 10 });
          if (col[3]) cv.validate(gr, c2, col[3]);
          c2 += col[2];
        });
      }
      cv.box(hr, 1, r0 + g.rows - 1, 12, C.INPUT_BORDER, 'SOLID', { h: true, v: true, color: C.LINE_L });
      grids[g.key] = { r0: r0, rows: g.rows, cols: colPos, spec: g.cols };
      if (g.prefill) for (var pk in g.prefill) g.prefill[pk].forEach(function (v, pi) { prefill.push([r0 + pi, colPos[pk], v]); });
    }
    var sp = cv.row(10); cv.fill(sp, 1, 12, C.CARD);
    var br = cv.row(34); cv.fill(br, 1, 12, C.CARD);
    var n = card.buttons.length, bspan = n === 3 ? 3 : (n === 2 ? 3 : 4);
    var bc = 13 - n * bspan;
    if (n === 3) cv.put(br, 1, 3, '', { bg: C.CARD });
    card.buttons.forEach(function (b) { drawButton_(cv, br, bc, bspan, b[0], b[1], b[2]); bc += bspan; });
    var bt = cv.row(10); cv.fill(bt, 1, 12, C.CARD);
    cv.box(top - 1, 1, bt, 12, C.LINE, 'SOLID');
    cv.spacer(16);
  });
  var foot = cv.row(20);
  cv.put(foot, 1, 12, 'Tip: to archive a record instead of deleting it, LOAD it, set Record Status = Archived, then UPDATE RECORD.', { fc: C.MUTED, fs: 8, i: true, ha: 'center' });
  return { canvas: cv, fields: fields, grids: grids, defaults: defaults, prefill: prefill };
}

var _DE_LAYOUT = null;
function deLayout_() { if (!_DE_LAYOUT) _DE_LAYOUT = buildDataEntry_(); return _DE_LAYOUT; }

/** Reads the whole Data Entry form: { fields:{key:value}, grids:{key:[{colKey:value,..,_i}]} } */
function readForm_() {
  var L = deLayout_();
  var sh = getSheet_(UMS.SHEETS.DE);
  var nR = Math.min(sh.getMaxRows(), L.canvas.rowCount());
  var vals = sh.getRange(1, 1, nR, 14).getValues();
  var out = { fields: {}, grids: {} };
  for (var k in L.fields) {
    var p = L.fields[k], v = vals[p[0]] ? vals[p[0]][p[1]] : '';
    out.fields[k] = (typeof v === 'string') ? v.trim() : v;
  }
  for (var g in L.grids) {
    var G = L.grids[g], rows = [];
    for (var i = 0; i < G.rows; i++) {
      var row = { _i: i };
      for (var ck in G.cols) { var v2 = vals[G.r0 + i][G.cols[ck]]; row[ck] = (typeof v2 === 'string') ? v2.trim() : v2; }
      rows.push(row);
    }
    out.grids[g] = rows;
  }
  return out;
}
function readDeContext_() {
  try { return readForm_().fields; } catch (e) { return {}; }
}

/**
 * Writes values into the form in ONE batch call.
 * fieldVals: {key:value}, gridVals: {gridKey:[{colKey:value}]}  (null row = clear)
 */
function writeForm_(fieldVals, gridVals, clearGrids) {
  var L = deLayout_();
  var sh = getSheet_(UMS.SHEETS.DE);
  var start = L.fields.ctxCountry[0];                      // first input row (canvas index)
  var nR = L.canvas.rowCount() - start;
  var rng = sh.getRange(start + 1, 1, nR, 14);
  var vals = rng.getValues();
  var set = function (r, c, v) { vals[r - start][c] = (v === undefined || v === null) ? '' : v; };
  for (var k in (fieldVals || {})) if (L.fields[k]) set(L.fields[k][0], L.fields[k][1], fieldVals[k]);
  (clearGrids || []).forEach(function (g) {
    var G = L.grids[g];
    for (var i = 0; i < G.rows; i++) for (var ck in G.cols) set(G.r0 + i, G.cols[ck], '');
  });
  for (var g2 in (gridVals || {})) {
    var G2 = L.grids[g2];
    gridVals[g2].forEach(function (row, i) {
      if (i >= G2.rows || !row) return;
      for (var ck2 in row) if (G2.cols[ck2] !== undefined) set(G2.r0 + i, G2.cols[ck2], row[ck2]);
    });
  }
  rng.setValues(vals);
}

/** The field keys belonging to one card (used by clear/load). */
function cardFieldKeys_(cardId) {
  var card = first_(DE_CARDS, function (c) { return c.id === cardId; });
  return card && card.fields ? card.fields.map(function (f) { return f[0]; }) : [];
}


/* ============================================================================
 *  RECORD CREATION (data layer) — used by the form buttons AND sample data
 *  Each create* validates, checks duplicates, generates the ID, writes the
 *  row and the audit entry.  Throws UmsError with a friendly message.
 * ========================================================================== */

function stamp_(rec, meta) {
  meta = meta || {};
  var now = new Date();
  rec.status = meta.status || rec.status || 'Pending Verification';
  if (rec.source === undefined || rec.source === '') rec.source = meta.source || '';
  if (rec.lastChecked === undefined || rec.lastChecked === '') rec.lastChecked = meta.lastChecked || '';
  rec.dataFlag = meta.dataFlag || rec.dataFlag || '';
  rec.created = now; rec.updated = now; rec.updatedBy = getUser_();
  return rec;
}
function dupError_(what, existing, extra) {
  return new UmsError('⚠ DUPLICATE — not saved.\n\n' + what + ' already exists' + (existing && existing.id ? ' (ID ' + existing.id + ')' : '') + '.' +
    (extra ? '\n\n' + extra : '\n\nTo change it: select it in ① SELECT RECORD → LOAD RECORD → edit → UPDATE RECORD.'));
}
function need_(v, label) { if (blank_(v)) throw new UmsError('"' + label + '" is required.'); return str_(v); }
function scopeOf_(ctx) {
  return ctx.p ? { programId: ctx.p.id, program: ctx.p.program, degree: ctx.p.degree }
    : { programId: 'ALL', program: '', degree: ctx.degree || 'All' };
}
function addAudit_(sheet, rec, extra) {
  var summary = {};
  for (var k in rec) if (k.charAt(0) !== '_' && ['created', 'updated', 'updatedBy', 'dataFlag'].indexOf(k) < 0 && !blank_(rec[k])) summary[schemaHeader_(sheet, k) || k] = rec[k];
  writeAuditLog('ADD', sheet, rec.id, rec.country, rec.university || rec.name || '', rec.program || '', '', summary);
}

function createCountry_(o, meta) {
  var name = need_(o.country, 'Country Name');
  if (checkDuplicate('country', { country: name, id: str_(o.id).toUpperCase() })) {
    throw dupError_('Country "' + name + '"' + (o.id ? ' or code ' + o.id : ''), checkDuplicate('country', { country: name, id: str_(o.id).toUpperCase() }), 'Countries are already available in every dropdown.');
  }
  var id = blank_(o.id) ? generateID('country', name) : str_(o.id).toUpperCase().replace(/[^A-Z0-9]/g, '');
  if (!/^[A-Z0-9]{2,4}$/.test(id)) throw new UmsError('Country Code must be 2–4 letters/digits (e.g. KR, AU, UK).');
  var rec = stamp_({ id: id, country: name, region: str_(o.region), currency: str_(o.currency), language: str_(o.language),
    active: str_(o.active) || 'Yes', notes: str_(o.notes) }, meta);
  appendRecord_(UMS.SHEETS.COUNTRIES, rec);
  addAudit_(UMS.SHEETS.COUNTRIES, rec);
  return rec;
}

function createUniversity_(o, meta) {
  var c = countryByName_(need_(o.country, 'Country'));
  if (!c) throw new UmsError('Country "' + o.country + '" does not exist yet. Add it in CARD 0 first.');
  var name = need_(o.name, 'University Name');
  var dup = checkDuplicate('university', { countryId: c.id, name: name });
  if (dup) throw dupError_('University "' + name + '" in ' + c.country, dup);
  var rec = stamp_({ id: generateID('university', c.id), countryId: c.id, country: c.country, name: name, city: str_(o.city),
    type: str_(o.type), website: str_(o.website), intlWebsite: str_(o.intlWebsite), logo: str_(o.logo), ranking: str_(o.ranking),
    accreditation: str_(o.accreditation), notes: str_(o.notes) }, meta);
  if (!blank_(o.website) && !/^https?:\/\//i.test(o.website)) throw new UmsError('Official Website must start with http:// or https://');
  appendRecord_(UMS.SHEETS.UNIVERSITIES, rec);
  addAudit_(UMS.SHEETS.UNIVERSITIES, rec);
  return rec;
}

function createCampus_(u, o, meta) {
  var name = need_(o.campus, 'Campus');
  var dup = checkDuplicate('campus', { uniId: u.id, campus: name });
  if (dup) throw dupError_('Campus "' + name + '" of ' + u.name, dup);
  var rec = stamp_({ id: generateID('campus', u.id), uniId: u.id, country: u.country, university: u.name, campus: name,
    city: str_(o.city) || str_(u.city), address: str_(o.address), notes: str_(o.notes) }, meta);
  appendRecord_(UMS.SHEETS.CAMPUSES, rec);
  addAudit_(UMS.SHEETS.CAMPUSES, rec);
  return rec;
}

function intakeName_(o) {
  if (!blank_(o.label)) return str_(o.label);
  return (str_(o.term) + ' ' + str_(o.year)).trim();
}
function createIntake_(u, o, meta) {
  need_(o.term, 'Intake');
  var year = toNum_(need_(o.year, 'Intake Year'), 'Intake Year', 2000, 2100);
  var name = intakeName_({ term: o.term, year: year, label: o.label });
  var degree = str_(o.degree) || 'All';
  var dup = checkDuplicate('intake', { uniId: u.id, intake: name, degree: degree });
  if (dup) throw dupError_('Intake "' + name + '" (' + degree + ') at ' + u.name, dup, 'To add another application round to it, select the intake in ① and press ＋ ADD APPLICATION ROUND.');
  var rec = stamp_({ id: generateID('intake', u.id), uniId: u.id, country: u.country, university: u.name, intake: name, term: str_(o.term),
    year: year, intakeType: str_(o.intakeType), degree: degree, campus: str_(o.campus), rolling: str_(o.rolling) || 'No', notes: str_(o.notes) }, meta);
  appendRecord_(UMS.SHEETS.INTAKES, rec);
  addAudit_(UMS.SHEETS.INTAKES, rec);
  return rec;
}

function roundDates_(o) {
  var d = {
    appStart: toDate_(o.appStart, 'Application Start'), appEnd: toDate_(o.appEnd, 'Application End'),
    interviewStart: toDate_(o.interviewStart, 'Interview Start'), interviewEnd: toDate_(o.interviewEnd, 'Interview End'),
    result: toDate_(o.result, 'Result Date'), depositDeadline: toDate_(o.depositDeadline, 'Deposit Deadline'),
    enrollDeadline: toDate_(o.enrollDeadline, 'Enrollment Deadline')
  };
  if (d.appStart && d.appEnd && d.appStart > d.appEnd) throw new UmsError('Application Start must be on or before Application End.');
  if (d.interviewStart && d.interviewEnd && d.interviewStart > d.interviewEnd) throw new UmsError('Interview Start must be on or before Interview End.');
  for (var k in d) if (d[k] === null) d[k] = '';
  return d;
}
function createRound_(intake, o, meta) {
  var round = need_(o.round, 'Application Round');
  if (round === 'Custom' && !blank_(o.customRound)) round = str_(o.customRound);
  var dup = checkDuplicate('round', { intakeId: intake.id, round: round });
  if (dup) throw dupError_(round + ' of intake "' + intake.intake + '"', dup);
  var d = roundDates_(o);
  var rec = stamp_({ id: generateID('round', intake.id), intakeId: intake.id, uniId: intake.uniId, country: intake.country,
    university: intake.university, intake: intake.intake, round: round, notes: str_(o.notes) }, meta);
  for (var k in d) rec[k] = d[k];
  appendRecord_(UMS.SHEETS.ROUNDS, rec);
  addAudit_(UMS.SHEETS.ROUNDS, rec);
  return rec;
}

function createProgram_(u, o, meta) {
  var degree = need_(o.degree, 'Degree Level'), name = need_(o.program, 'Program Name');
  var dup = checkDuplicate('program', { uniId: u.id, degree: degree, program: name, campus: o.campus });
  if (dup) throw dupError_(degree + ' program "' + name + '" at ' + u.name, dup);
  var rec = stamp_({ id: generateID('program', u.id), uniId: u.id, country: u.country, university: u.name, campus: str_(o.campus),
    degree: degree, faculty: str_(o.faculty), department: str_(o.department), program: name, major: str_(o.major) || name,
    duration: str_(o.duration), studyMode: str_(o.studyMode) || 'Full-time', language: str_(o.language), intakes: str_(o.intakes), notes: str_(o.notes) }, meta);
  appendRecord_(UMS.SHEETS.PROGRAMS, rec);
  addAudit_(UMS.SHEETS.PROGRAMS, rec);
  return rec;
}

function createAcademic_(u, scope, o, meta) {
  var rec = { uniId: u.id, programId: scope.programId, country: u.country, university: u.name, program: scope.program, degree: scope.degree,
    minGpa: toNum_(o.minGpa, 'Minimum GPA', 0, 100), gpaScale: str_(o.gpaScale), prevDegree: str_(o.prevDegree), background: str_(o.background),
    minPercent: toNum_(o.minPercent, 'Minimum Percentage', 0, 100), workExp: str_(o.workExp), entranceExam: str_(o.entranceExam),
    interview: str_(o.interview), other: str_(o.other) };
  var any = ['minGpa', 'gpaScale', 'prevDegree', 'background', 'minPercent', 'workExp', 'entranceExam', 'interview', 'other'].some(function (k) { return !blank_(rec[k]); });
  if (!any) throw new UmsError('Fill at least one academic requirement field.');
  var dup = checkDuplicate('academic', rec);
  if (dup) throw dupError_('Academic requirements for ' + (scope.program || 'all ' + scope.degree + ' programs'), dup);
  rec.id = generateID('academic', u.id);
  stamp_(rec, meta);
  appendRecord_(UMS.SHEETS.ACADEMIC, rec);
  addAudit_(UMS.SHEETS.ACADEMIC, rec);
  return rec;
}

function createLanguage_(u, scope, o, meta) {
  var test = need_(o.test, 'Test');
  var score = validateScore_(test, o.minScore, test + ' minimum score');
  var rec = { uniId: u.id, programId: scope.programId, country: u.country, university: u.name, program: scope.program, degree: scope.degree,
    test: test, minScore: score, subScore: str_(o.subScore), waiver: str_(o.waiver), notes: str_(o.notes) };
  var dup = checkDuplicate('language', rec);
  if (dup) throw dupError_(test + ' requirement for ' + (scope.program || 'all ' + scope.degree + ' programs'), dup);
  rec.id = generateID('language', u.id);
  stamp_(rec, meta);
  appendRecord_(UMS.SHEETS.LANGUAGE, rec);
  addAudit_(UMS.SHEETS.LANGUAGE, rec);
  return rec;
}

function createFee_(u, scope, o, meta) {
  var type = need_(o.feeType, 'Fee Type');
  var amount = toNum_(need_(o.amount, type + ' amount'), type + ' amount', 0);
  var rec = { uniId: u.id, programId: scope.programId, country: u.country, university: u.name, program: scope.program, degree: scope.degree,
    feeType: type, amount: amount, currency: str_(o.currency) || currencyOf_(u), period: str_(o.period) || (type === 'Tuition Fee' ? '' : 'One-time'), notes: str_(o.notes) };
  if (type === 'Tuition Fee' && blank_(rec.period)) throw new UmsError('Please choose the Period for the Tuition Fee (per semester, per year, per credit …).');
  if (blank_(rec.currency)) throw new UmsError('Please choose a Currency for ' + type + '.');
  var dup = checkDuplicate('fee', rec);
  if (dup) throw dupError_(type + ' for ' + (scope.program || 'all ' + scope.degree + ' programs'), dup);
  rec.id = generateID('fee', u.id);
  stamp_(rec, meta);
  appendRecord_(UMS.SHEETS.FEES, rec);
  addAudit_(UMS.SHEETS.FEES, rec);
  return rec;
}
function currencyOf_(u) { var c = findBy_(UMS.SHEETS.COUNTRIES, 'id', u.countryId); return c ? str_(c.currency) : ''; }

function createScholarship_(u, scope, o, meta) {
  var name = need_(o.name, 'Scholarship Name');
  var rec = { uniId: u.id, programId: scope.programId, country: u.country, university: u.name, program: scope.program, degree: scope.degree,
    name: name, schType: str_(o.schType), mode: str_(o.mode), eligibility: str_(o.eligibility), minGpa: toNum_(o.minGpa, 'Minimum GPA', 0, 100),
    percent: toNum_(o.percent, 'Scholarship %', 0, 100), amount: toNum_(o.amount, 'Scholarship amount', 0), currency: str_(o.currency),
    duration: str_(o.duration), renewal: str_(o.renewal), notes: str_(o.notes) };
  var dup = checkDuplicate('scholarship', rec);
  if (dup) throw dupError_('Scholarship "' + name + '" for ' + (scope.program || 'all ' + scope.degree + ' programs'), dup);
  rec.id = generateID('scholarship', u.id);
  stamp_(rec, meta);
  appendRecord_(UMS.SHEETS.SCHOLARSHIPS, rec);
  addAudit_(UMS.SHEETS.SCHOLARSHIPS, rec);
  return rec;
}

function createTier_(sch, o, meta) {
  var tier = need_(o.tier, 'Tier Name');
  var rec = { schId: sch.id, uniId: sch.uniId, country: sch.country, university: sch.university, scholarship: sch.name, tier: tier,
    minGpa: toNum_(o.minGpa, 'Tier Min GPA', 0, 100), ielts: validateScore_('IELTS', o.ielts, 'Tier IELTS'),
    toefl: validateScore_('TOEFL iBT', o.toefl, 'Tier TOEFL iBT'), pte: validateScore_('PTE Academic', o.pte, 'Tier PTE'),
    topik: validateScore_('TOPIK', o.topik, 'Tier TOPIK'), otherTest: str_(o.otherTest), otherScore: str_(o.otherScore),
    percent: toNum_(o.percent, 'Tier Scholarship %', 0, 100), amount: toNum_(o.amount, 'Tier amount', 0), currency: str_(o.currency) || str_(sch.currency), notes: str_(o.notes) };
  if (blank_(rec.percent) && blank_(rec.amount)) throw new UmsError('Tier "' + tier + '": enter a Scholarship % or an Amount.');
  var dup = checkDuplicate('tier', rec);
  if (dup) throw dupError_('Tier "' + tier + '" of ' + sch.name, dup);
  rec.id = generateID('tier', sch.id);
  stamp_(rec, meta);
  appendRecord_(UMS.SHEETS.TIERS, rec);
  addAudit_(UMS.SHEETS.TIERS, rec);
  return rec;
}

function createDocument_(u, scope, o, meta) {
  var doc = need_(o.document, 'Document');
  var rec = { uniId: u.id, programId: scope.programId, country: u.country, university: u.name, program: scope.program, degree: scope.degree,
    document: doc, level: str_(o.level) || 'Mandatory', spec: str_(o.spec), notes: str_(o.notes) };
  var dup = checkDuplicate('document', rec);
  if (dup) throw dupError_('Document "' + doc + '" for ' + (scope.program || 'all ' + scope.degree + ' programs'), dup);
  rec.id = generateID('document', u.id);
  stamp_(rec, meta);
  appendRecord_(UMS.SHEETS.DOCUMENTS, rec);
  addAudit_(UMS.SHEETS.DOCUMENTS, rec);
  return rec;
}

function createCSR_(country, u, o, meta) {
  var req = need_(o.requirement, 'Requirement');
  var rec = { countryId: country.id, country: country.country, uniId: u ? u.id : 'ALL', university: u ? u.name : '', degree: str_(o.degree) || 'All',
    category: str_(o.category) || 'Other', requirement: req, details: str_(o.details), mandatory: str_(o.mandatory) || 'Yes', notes: str_(o.notes) };
  var dup = checkDuplicate('csr', rec);
  if (dup) throw dupError_('Requirement "' + req + '" for ' + (u ? u.name : country.country), dup);
  rec.id = generateID('csr', country.id);
  stamp_(rec, meta);
  appendRecord_(UMS.SHEETS.CSR, rec);
  addAudit_(UMS.SHEETS.CSR, rec);
  return rec;
}

function createVisa_(country, o, meta) {
  var type = need_(o.visaType, 'Visa Type');
  var rec = { countryId: country.id, country: country.country, visaType: type, financial: str_(o.financial), processing: str_(o.processing),
    workRights: str_(o.workRights), url: str_(o.url), notes: str_(o.notes) };
  var dup = checkDuplicate('visa', rec);
  if (dup) throw dupError_('Visa "' + type + '" for ' + country.country, dup);
  rec.id = generateID('visa', country.id);
  stamp_(rec, meta);
  appendRecord_(UMS.SHEETS.VISA, rec);
  addAudit_(UMS.SHEETS.VISA, rec);
  return rec;
}

function createSource_(u, o, meta) {
  var url = need_(o.url, 'Source URL');
  var rec = { uniId: u.id, country: u.country, university: u.name, sourceType: str_(o.sourceType) || 'Other', title: str_(o.title), url: url,
    checkedOn: toDate_(o.checkedOn, 'Last Checked') || new Date(), checkedBy: str_(o.checkedBy) || getUser_(), notes: str_(o.notes) };
  var dup = checkDuplicate('source', rec);
  if (dup) throw dupError_('Source ' + url, dup);
  rec.id = generateID('source', u.id);
  stamp_(rec, meta);
  appendRecord_(UMS.SHEETS.SOURCES, rec);
  addAudit_(UMS.SHEETS.SOURCES, rec);
  return rec;
}

/** Updates an existing record, with duplicate protection and audit trail. */
function updateEntity_(sheet, rec, changes, dupType, dupProbe) {
  if (dupType) {
    var probe = {};
    for (var k in rec) probe[k] = rec[k];
    for (var k2 in (dupProbe || changes)) probe[k2] = (dupProbe || changes)[k2];
    var dup = checkDuplicate(dupType, probe, rec.id);
    if (dup) throw dupError_('Another record with the same key', dup, 'Your update was cancelled so that no duplicate is created.');
  }
  var changed = updateRow_(sheet, rec._row, changes);
  if (Object.keys(changed).length) {
    updateRow_(sheet, rec._row, { updated: new Date(), updatedBy: getUser_() });
    auditChanges_('UPDATE', sheet, rec, changed);
  }
  return Object.keys(changed).length;
}

/* ============================================================================
 *  FORM CONTEXT
 * ========================================================================== */
function formMeta_(F) {
  return { status: str_(F.metaStatus) || 'Pending Verification', source: str_(F.metaSource), lastChecked: toDate_(F.metaChecked, 'Last Checked') || '' };
}
function formCtx_(F, level) {
  var c = countryByName_(F.ctxCountry);
  if (!c) throw new UmsError('In ① SELECT RECORD, choose the Country first.');
  if (level === 'country') return { country: c };
  var u = universityByName_(F.ctxUniversity, F.ctxCountry);
  if (!u) throw new UmsError('In ① SELECT RECORD, choose the University (Country → University).\n\nNew university? Add it with CARD 1 first.');
  var p = null;
  if (!blank_(F.ctxProgram)) {
    p = programByLabel_(u.id, F.ctxProgram);
    if (!p) throw new UmsError('Program "' + F.ctxProgram + '" was not found for ' + u.name + '. Pick it again from the dropdown.');
  }
  if (level === 'program' && !p) throw new UmsError('In ① SELECT RECORD, choose the Program.');
  return { country: c, u: u, p: p, degree: p ? p.degree : str_(F.ctxDegree) };
}

function afterChange_(msg, formVals, gridVals) {
  if (formVals || gridVals) writeForm_(formVals || {}, gridVals || {});
  _CACHE = {};
  try { refreshDropdowns(); } catch (e) { logError_('refreshDropdowns', e); }
  try { renderHome_(false); } catch (e2) { logError_('renderHome', e2); }
  try { renderUniversitySearch_(); } catch (e3) { logError_('renderUniversitySearch', e3); }
  if (msg) notify_(msg, '✔ Saved');
}

/* ============================================================================
 *  FORM BUTTONS — ADD
 * ========================================================================== */
function addCountry() {
  run_('Add Country', function () {
    var F = readForm_().fields;
    var rec = createCountry_({ country: F.coName, id: F.coCode, region: F.coRegion, currency: F.coCurrency, language: F.coLanguage, active: F.coActive }, formMeta_(F));
    afterChange_('Country "' + rec.country + '" added with code ' + rec.id + '.\n\nIt now appears in every Country dropdown and on the HOME dashboard.',
      { coCode: rec.id, ctxCountry: rec.country, uniCountry: rec.country });
    return rec;
  });
}

function addVisaInformation() {
  run_('Add Visa Info', function () {
    var F = readForm_().fields;
    var c = countryByName_(F.coName) || countryByName_(F.ctxCountry);
    if (!c) throw new UmsError('Enter an existing Country Name in CARD 0 (or choose a Country in ①).');
    var rec = createVisa_(c, { visaType: F.viType, financial: F.viFinancial, processing: F.viProcessing, workRights: F.viWork, url: F.viUrl }, formMeta_(F));
    afterChange_('Visa information "' + rec.visaType + '" added for ' + c.country + ' (' + rec.id + ').');
  });
}

function addUniversity() {
  run_('Add University', function () {
    var F = readForm_().fields, meta = formMeta_(F);
    var u = createUniversity_({ country: F.uniCountry || F.ctxCountry, name: F.uniName, city: F.uniCity, type: F.uniType, website: F.uniWebsite,
      intlWebsite: F.uniIntl, logo: F.uniLogo, ranking: F.uniRanking, accreditation: F.uniAccreditation, notes: F.uniNotes }, meta);
    var campus = createCampus_(u, { campus: F.uniCampus || 'Main Campus', city: F.uniCity, address: F.uniAddress }, meta);
    if (!blank_(meta.source)) { try { createSource_(u, { url: meta.source, sourceType: 'Official Website', title: 'Initial source' }, meta); } catch (e) { } }
    afterChange_('University added:\n\n' + u.name + '\nID: ' + u.id + '\nCampus: ' + campus.campus + ' (' + campus.id + ')\n\nIt is now selected in ① — continue with Intake, Program, Fees …',
      { uniId: u.id, uniCampusId: campus.id, ctxCountry: u.country, ctxUniversity: u.name, ctxProgram: '', ctxIntake: '', ctxRound: '' });
  });
}

function addCampus() {
  run_('Add Campus', function () {
    var F = readForm_().fields;
    var ctx = formCtx_(F, 'university');
    var c = createCampus_(ctx.u, { campus: F.uniCampus, city: F.uniCity, address: F.uniAddress }, formMeta_(F));
    afterChange_('Campus "' + c.campus + '" added to ' + ctx.u.name + ' (' + c.id + ').', { uniCampusId: c.id });
  });
}

function addIntake() {
  run_('Add Intake', function () {
    var F = readForm_().fields, meta = formMeta_(F);
    var ctx = formCtx_(F, 'university');
    var label = F.inTerm === 'Custom' ? F.inRoundCustom : '';
    var it = createIntake_(ctx.u, { term: F.inTerm, year: F.inYear, label: label, intakeType: F.inType, degree: F.inDegree, campus: F.inCampus,
      rolling: F.inRolling, notes: F.inNotes }, meta);
    var extra = '', vals = { inIntakeId: it.id, ctxIntake: it.intake };
    if (!blank_(F.inRound) || !blank_(F.inAppEnd)) {
      var r = createRound_(it, roundFromForm_(F), meta);
      extra = '\nApplication round: ' + r.round + ' (' + r.id + ')';
      vals.inRoundId = r.id; vals.ctxRound = r.round;
    }
    afterChange_('Intake "' + it.intake + '" added to ' + ctx.u.name + ' (' + it.id + ').' + extra, vals);
  });
}
function roundFromForm_(F) {
  return { round: F.inRound || (F.inRolling === 'Yes' ? 'Rolling Admission' : 'Round 1'), customRound: F.inRoundCustom, appStart: F.inAppStart, appEnd: F.inAppEnd,
    interviewStart: F.inIntStart, interviewEnd: F.inIntEnd, result: F.inResult, depositDeadline: F.inDeposit, enrollDeadline: F.inEnroll, notes: F.inNotes };
}

function addApplicationRound() {
  run_('Add Application Round', function () {
    var F = readForm_().fields;
    var ctx = formCtx_(F, 'university');
    var it = intakeByName_(ctx.u.id, F.ctxIntake);
    if (!it && !blank_(F.inTerm) && !blank_(F.inYear)) it = intakeByName_(ctx.u.id, intakeName_({ term: F.inTerm, year: F.inYear, label: F.inTerm === 'Custom' ? F.inRoundCustom : '' }));
    if (!it) throw new UmsError('Choose the Intake in ① SELECT RECORD (or add the intake first with ＋ ADD INTAKE).');
    if (blank_(F.inRound)) throw new UmsError('Choose the Application Round in CARD 2 (Round 1, Round 2 … Custom).');
    var r = createRound_(it, roundFromForm_(F), formMeta_(F));
    afterChange_(r.round + ' added to intake "' + it.intake + '" (' + r.id + ').', { inRoundId: r.id, inIntakeId: it.id, ctxIntake: it.intake, ctxRound: r.round });
  });
}

function addProgram() {
  run_('Add Program', function () {
    var F = readForm_().fields;
    var ctx = formCtx_(F, 'university');
    var p = createProgram_(ctx.u, { degree: F.prDegree, program: F.prName, faculty: F.prFaculty, department: F.prDepartment, major: F.prMajor,
      duration: F.prDuration, studyMode: F.prMode, language: F.prLanguage, campus: F.prCampus, intakes: F.prIntakes, notes: F.prNotes }, formMeta_(F));
    afterChange_(p.degree + ' program added:\n\n' + p.program + '\nID: ' + p.id + '\n\nIt is now selected in ① — add its requirements, fees and scholarships in Cards 4–8.',
      { prId: p.id, ctxDegree: p.degree, ctxProgram: programLabel_(p) });
  });
}

function addAcademicRequirement() {
  run_('Add Academic Requirement', function () {
    var F = readForm_().fields;
    var ctx = formCtx_(F, 'university');
    var a = createAcademic_(ctx.u, scopeOf_(ctx), { minGpa: F.acMinGpa, gpaScale: F.acScale, prevDegree: F.acPrev, background: F.acBackground,
      minPercent: F.acPercent, workExp: F.acWork, entranceExam: F.acExam, interview: F.acInterview, other: F.acOther }, formMeta_(F));
    afterChange_('Academic requirements saved (' + a.id + ') for ' + (a.program || 'all ' + a.degree + ' programs') + '.', { acId: a.id });
  });
}

/** Adds every filled row of a grid that has no Record ID yet. */
function addGridRows_(gridKey, isFilled, create, label) {
  var form = readForm_();
  var rows = form.grids[gridKey].filter(function (r) { return blank_(r.id) && isFilled(r); });
  if (!rows.length) throw new UmsError('Nothing to add — fill at least one ' + label + ' row (rows that already show a Record ID are skipped).');
  var added = [], skipped = [], ids = [];
  rows.forEach(function (r) {
    try {
      var rec = create(r, form.fields);
      added.push(rec); ids[r._i] = { id: rec.id };
    } catch (e) {
      if (e && e.name === 'UmsError') skipped.push('• ' + e.message.split('\n\n')[0].replace('⚠ DUPLICATE — not saved.', 'Duplicate').replace(/\n/g, ' ') + ' (row ' + (r._i + 1) + ')');
      else throw e;
      _CACHE = {};
    }
  });
  var gv = {}; gv[gridKey] = ids;
  var msg = added.length + ' ' + label + (added.length === 1 ? '' : 's') + ' added.';
  if (skipped.length) msg += '\n\nNot added:\n' + skipped.join('\n');
  afterChange_(msg, {}, gv);
  return added;
}

function addLanguageRequirement() {
  run_('Add Language Requirement', function () {
    var F = readForm_().fields, ctx = formCtx_(F, 'university'), scope = scopeOf_(ctx), meta = formMeta_(F);
    addGridRows_('langGrid', function (r) { return !blank_(r.test); }, function (r) {
      return createLanguage_(ctx.u, scope, { test: r.test, minScore: r.minScore, subScore: r.subScore, waiver: r.waiver }, meta);
    }, 'language requirement');
  });
}

function addFee() {
  run_('Add Fee', function () {
    var F = readForm_().fields, ctx = formCtx_(F, 'university'), scope = scopeOf_(ctx), meta = formMeta_(F);
    addGridRows_('feeGrid', function (r) { return !blank_(r.feeType) && !blank_(r.amount); }, function (r) {
      return createFee_(ctx.u, scope, { feeType: r.feeType, amount: r.amount, currency: r.currency, period: r.period, notes: r.notes }, meta);
    }, 'fee');
  });
}

function addScholarship() {
  run_('Add Scholarship', function () {
    var F = readForm_().fields, ctx = formCtx_(F, 'university');
    var s = createScholarship_(ctx.u, scopeOf_(ctx), { name: F.scName, schType: F.scType, mode: F.scMode, eligibility: F.scEligibility, minGpa: F.scMinGpa,
      percent: F.scPercent, amount: F.scAmount, currency: F.scCurrency, duration: F.scDuration, renewal: F.scRenewal }, formMeta_(F));
    afterChange_('Scholarship "' + s.name + '" added (' + s.id + ').\n\nTo add score-based tiers, fill the tier rows and press ＋ ADD SCHOLARSHIP TIER.',
      { scId: s.id, scExisting: s.name });
  });
}

function addScholarshipTier() {
  run_('Add Scholarship Tier', function () {
    var F = readForm_().fields, ctx = formCtx_(F, 'university'), meta = formMeta_(F);
    var sch = null;
    if (!blank_(F.scId)) sch = findBy_(UMS.SHEETS.SCHOLARSHIPS, 'id', F.scId);
    if (!sch && !blank_(F.scExisting)) sch = first_(readTable_(UMS.SHEETS.SCHOLARSHIPS).objs, function (s) { return s.uniId === ctx.u.id && norm_(s.name) === norm_(F.scExisting); });
    if (!sch) throw new UmsError('Choose the scholarship in "Existing Scholarship (for tiers)" — or add the scholarship first.');
    addGridRows_('tierGrid', function (r) { return !blank_(r.tier); }, function (r) {
      return createTier_(sch, r, meta);
    }, 'scholarship tier');
  });
}

function addDocumentRequirement() {
  run_('Add Document', function () {
    var F = readForm_().fields, ctx = formCtx_(F, 'university'), scope = scopeOf_(ctx), meta = formMeta_(F);
    addGridRows_('docGrid', function (r) { return !blank_(r.document); }, function (r) {
      return createDocument_(ctx.u, scope, { document: r.document, level: r.level, spec: r.spec }, meta);
    }, 'document requirement');
  });
}

function addCountrySpecificRequirement() {
  run_('Add Country Requirement', function () {
    var F = readForm_().fields, meta = formMeta_(F);
    var c = countryByName_(F.ctxCountry);
    if (!c) throw new UmsError('In ① SELECT RECORD, choose the Country first.');
    var u = universityByName_(F.ctxUniversity, F.ctxCountry);
    addGridRows_('csrGrid', function (r) { return !blank_(r.requirement); }, function (r) {
      var scopeUni = r.scope === 'University' ? u : null;
      if (r.scope === 'University' && !u) throw new UmsError('Scope "University" needs a University selected in ①.');
      return createCSR_(c, scopeUni, { category: r.category, requirement: r.requirement, details: r.details, mandatory: r.mandatory, degree: F.ctxDegree }, meta);
    }, 'country requirement');
  });
}

function addSource() {
  run_('Add Source', function () {
    var F = readForm_().fields, ctx = formCtx_(F, 'university'), meta = formMeta_(F);
    addGridRows_('srcGrid', function (r) { return !blank_(r.url); }, function (r) {
      return createSource_(ctx.u, r, meta);
    }, 'source');
  });
}

/* ============================================================================
 *  LOAD  →  EDIT  →  UPDATE
 * ========================================================================== */
function btnLoadRecord() { run_('Load Record', function () { loadRecord_(); }, { lock: false }); }

function loadRecord_() {
  var F = readForm_().fields;
  var ctx = formCtx_(F, 'university');
  var u = ctx.u, S = UMS.SHEETS;
  clearCards_(false);
  var v = {}, g = {};
  var camp = filterBy_(S.CAMPUSES, function (c) { return c.uniId === u.id; });
  v.uniCountry = u.country; v.uniName = u.name; v.uniCity = u.city; v.uniType = u.type; v.uniRanking = u.ranking; v.uniWebsite = u.website;
  v.uniIntl = u.intlWebsite; v.uniLogo = u.logo; v.uniAccreditation = u.accreditation; v.uniNotes = u.notes; v.uniId = u.id;
  if (camp.length) { v.uniCampus = camp[0].campus; v.uniAddress = camp[0].address; v.uniCampusId = camp[0].id; }
  v.metaStatus = u.status; v.metaChecked = u.lastChecked; v.metaSource = u.source;
  v.coName = '';

  var it = intakeByName_(u.id, F.ctxIntake);
  if (it) {
    v.inTerm = it.term; v.inYear = it.year; v.inType = it.intakeType; v.inDegree = it.degree === 'All' ? '' : it.degree; v.inCampus = it.campus;
    v.inRolling = it.rolling; v.inNotes = it.notes; v.inIntakeId = it.id;
    if (it.term === 'Custom') v.inRoundCustom = it.intake;
    var rounds = filterBy_(S.ROUNDS, function (r) { return r.intakeId === it.id; });
    var rd = first_(rounds, function (r) { return norm_(r.round) === norm_(F.ctxRound); }) || rounds[0];
    if (rd) {
      v.inRound = getList_('ROUND').indexOf(rd.round) >= 0 ? rd.round : 'Custom';
      if (v.inRound === 'Custom') v.inRoundCustom = rd.round;
      v.inAppStart = rd.appStart; v.inAppEnd = rd.appEnd; v.inIntStart = rd.interviewStart; v.inIntEnd = rd.interviewEnd;
      v.inResult = rd.result; v.inDeposit = rd.depositDeadline; v.inEnroll = rd.enrollDeadline; v.inRoundId = rd.id; v.ctxRound = rd.round;
    }
  }
  var p = ctx.p;
  var scopeMatch;
  if (p) {
    v.prDegree = p.degree; v.prName = p.program; v.prFaculty = p.faculty; v.prDepartment = p.department; v.prMajor = p.major;
    v.prDuration = p.duration; v.prMode = p.studyMode; v.prLanguage = p.language; v.prCampus = p.campus; v.prIntakes = p.intakes; v.prNotes = p.notes; v.prId = p.id;
    scopeMatch = function (x) { return String(x.programId) === String(p.id); };
  } else {
    var deg = str_(F.ctxDegree);
    scopeMatch = function (x) { return (str_(x.programId).toUpperCase() === 'ALL' || blank_(x.programId)) && (!deg || x.degree === deg || blank_(x.degree) || x.degree === 'All'); };
  }
  var mineScoped = function (t) { return filterBy_(t, function (x) { return x.uniId === u.id && scopeMatch(x); }); };
  var ac = mineScoped(S.ACADEMIC)[0];
  if (ac) {
    v.acMinGpa = ac.minGpa; v.acScale = ac.gpaScale; v.acPrev = ac.prevDegree; v.acBackground = ac.background; v.acPercent = ac.minPercent;
    v.acWork = ac.workExp; v.acExam = ac.entranceExam; v.acInterview = ac.interview; v.acOther = ac.other; v.acId = ac.id;
  }
  g.langGrid = mineScoped(S.LANGUAGE).map(function (x) { return { test: x.test, minScore: x.minScore, subScore: x.subScore, waiver: x.waiver, id: x.id }; });
  g.feeGrid = mineScoped(S.FEES).map(function (x) { return { feeType: x.feeType, amount: x.amount, currency: x.currency, period: x.period, notes: x.notes, id: x.id }; });
  var sch = mineScoped(S.SCHOLARSHIPS)[0];
  if (sch) {
    v.scName = sch.name; v.scType = sch.schType; v.scMode = sch.mode; v.scEligibility = sch.eligibility; v.scMinGpa = sch.minGpa; v.scPercent = sch.percent;
    v.scAmount = sch.amount; v.scCurrency = sch.currency; v.scDuration = sch.duration; v.scRenewal = sch.renewal; v.scExisting = sch.name; v.scId = sch.id;
    g.tierGrid = filterBy_(S.TIERS, function (t) { return t.schId === sch.id; }).map(function (t) {
      return { tier: t.tier, minGpa: t.minGpa, ielts: t.ielts, toefl: t.toefl, pte: t.pte, topik: t.topik, otherTest: t.otherTest, otherScore: t.otherScore, percent: t.percent, amount: t.amount, id: t.id };
    });
  }
  g.docGrid = mineScoped(S.DOCUMENTS).map(function (x) { return { document: x.document, level: x.level, spec: x.spec, id: x.id }; });
  g.csrGrid = filterBy_(S.CSR, function (x) { return x.countryId === u.countryId && (x.uniId === u.id || str_(x.uniId).toUpperCase() === 'ALL' || blank_(x.uniId)); })
    .map(function (x) { return { category: x.category, requirement: x.requirement, details: x.details, mandatory: x.mandatory, scope: x.uniId === u.id ? 'University' : 'Country', id: x.id }; });
  g.srcGrid = filterBy_(S.SOURCES, function (x) { return x.uniId === u.id; })
    .map(function (x) { return { sourceType: x.sourceType, title: x.title, url: x.url, checkedOn: x.checkedOn, checkedBy: x.checkedBy, id: x.id }; });
  var L = deLayout_();
  var over = [];
  for (var gk in g) if (g[gk].length > L.grids[gk].rows) { over.push(gk); g[gk] = g[gk].slice(0, L.grids[gk].rows); }
  writeForm_(v, g);
  toast_('Loaded ' + u.name + (p ? ' · ' + p.program : '') + (it ? ' · ' + it.intake : '') + '. Edit the cards, then press UPDATE RECORD.' +
    (over.length ? ' (Some lists have more rows than the form shows — edit the extra rows in the database sheet.)' : ''), 'Record loaded', 8);
}

function updateRecord() {
  run_('Update Record', function () {
    var form = readForm_(), F = form.fields, S = UMS.SHEETS, meta = formMeta_(F);
    if (blank_(F.uniId)) throw new UmsError('Nothing loaded yet.\n\nIn ① SELECT RECORD choose Country → University (and optionally Degree → Program → Intake), press LOAD RECORD, edit, then UPDATE RECORD.');
    var u = findBy_(S.UNIVERSITIES, 'id', F.uniId);
    if (!u) throw new UmsError('University ' + F.uniId + ' no longer exists.');
    var n = 0, added = 0, notes = [];

    // University (+ status from ①)
    var c = countryByName_(F.uniCountry) || findBy_(S.COUNTRIES, 'id', u.countryId);
    if (!c) throw new UmsError('Country "' + F.uniCountry + '" does not exist.');
    if (!blank_(F.uniWebsite) && !/^https?:\/\//i.test(F.uniWebsite)) throw new UmsError('Official Website must start with http:// or https://');
    n += updateEntity_(S.UNIVERSITIES, u, { name: need_(F.uniName, 'University Name'), countryId: c.id, country: c.country, city: str_(F.uniCity), type: str_(F.uniType),
      website: str_(F.uniWebsite), intlWebsite: str_(F.uniIntl), logo: str_(F.uniLogo), ranking: str_(F.uniRanking), accreditation: str_(F.uniAccreditation),
      notes: str_(F.uniNotes), status: meta.status, source: meta.source, lastChecked: meta.lastChecked }, 'university');
    u = findBy_(S.UNIVERSITIES, 'id', F.uniId);
    if (!blank_(F.uniCampusId)) {
      var camp = findBy_(S.CAMPUSES, 'id', F.uniCampusId);
      if (camp) n += updateEntity_(S.CAMPUSES, camp, { campus: need_(F.uniCampus, 'Campus'), city: str_(F.uniCity), address: str_(F.uniAddress), university: u.name, country: u.country }, 'campus');
    }
    // Intake + round
    if (!blank_(F.inIntakeId)) {
      var it = findBy_(S.INTAKES, 'id', F.inIntakeId);
      if (it) {
        var year = toNum_(F.inYear, 'Intake Year', 2000, 2100);
        var nm = intakeName_({ term: F.inTerm, year: year, label: F.inTerm === 'Custom' ? F.inRoundCustom : '' });
        n += updateEntity_(S.INTAKES, it, { intake: nm, term: str_(F.inTerm), year: year, intakeType: str_(F.inType), degree: str_(F.inDegree) || 'All',
          campus: str_(F.inCampus), rolling: str_(F.inRolling), notes: str_(F.inNotes), university: u.name, country: u.country }, 'intake');
        if (!blank_(F.inRoundId)) {
          var rd = findBy_(S.ROUNDS, 'id', F.inRoundId);
          if (rd) {
            var d = roundDates_(roundFromForm_(F));
            var rn = F.inRound === 'Custom' && !blank_(F.inRoundCustom) ? str_(F.inRoundCustom) : str_(F.inRound) || rd.round;
            d.round = rn; d.intake = nm; d.university = u.name; d.country = u.country;
            n += updateEntity_(S.ROUNDS, rd, d, 'round');
          }
        }
      }
    }
    // Program
    var p = null;
    if (!blank_(F.prId)) {
      p = findBy_(S.PROGRAMS, 'id', F.prId);
      if (p) {
        n += updateEntity_(S.PROGRAMS, p, { degree: need_(F.prDegree, 'Degree Level'), program: need_(F.prName, 'Program Name'), faculty: str_(F.prFaculty),
          department: str_(F.prDepartment), major: str_(F.prMajor), duration: str_(F.prDuration), studyMode: str_(F.prMode), language: str_(F.prLanguage),
          campus: str_(F.prCampus), intakes: str_(F.prIntakes), notes: str_(F.prNotes), university: u.name, country: u.country }, 'program');
        p = findBy_(S.PROGRAMS, 'id', F.prId);
      }
    }
    var scope = p ? { programId: p.id, program: p.program, degree: p.degree } : { programId: 'ALL', program: '', degree: str_(F.ctxDegree) || 'All' };
    // Academic
    var acVals = { minGpa: toNum_(F.acMinGpa, 'Minimum GPA', 0, 100), gpaScale: str_(F.acScale), prevDegree: str_(F.acPrev), background: str_(F.acBackground),
      minPercent: toNum_(F.acPercent, 'Minimum Percentage', 0, 100), workExp: str_(F.acWork), entranceExam: str_(F.acExam), interview: str_(F.acInterview), other: str_(F.acOther) };
    if (!blank_(F.acId)) {
      var ac = findBy_(S.ACADEMIC, 'id', F.acId);
      if (ac) n += updateEntity_(S.ACADEMIC, ac, acVals);
    } else if (Object.keys(acVals).some(function (k) { return !blank_(acVals[k]); })) {
      var na = createAcademic_(u, scope, acVals, meta); added++; writeForm_({ acId: na.id });
    }
    // Grids
    var gridIds = {};
    var doGrid = function (gridKey, sheet, filled, toChanges, create, dupType) {
      gridIds[gridKey] = [];
      form.grids[gridKey].forEach(function (r) {
        if (!blank_(r.id)) {
          var rec = findBy_(sheet, 'id', r.id);
          if (!rec) { notes.push('• ' + r.id + ' not found (skipped)'); return; }
          if (!filled(r)) { notes.push('• ' + r.id + ' left unchanged (row was emptied — set Status = Archived in the database to retire it)'); return; }
          n += updateEntity_(sheet, rec, toChanges(r), dupType);
        } else if (filled(r)) {
          try { var nr = create(r); added++; gridIds[gridKey][r._i] = { id: nr.id }; }
          catch (e) { if (e && e.name === 'UmsError') notes.push('• ' + e.message.split('\n\n')[0].replace('⚠ DUPLICATE — not saved.', 'Duplicate').replace(/\n/g, ' ')); else throw e; }
        }
      });
    };
    doGrid('langGrid', S.LANGUAGE, function (r) { return !blank_(r.test); },
      function (r) { return { test: r.test, minScore: validateScore_(r.test, r.minScore), subScore: str_(r.subScore), waiver: str_(r.waiver) }; },
      function (r) { return createLanguage_(u, scope, r, meta); }, 'language');
    doGrid('feeGrid', S.FEES, function (r) { return !blank_(r.feeType) && !blank_(r.amount); },
      function (r) { return { feeType: r.feeType, amount: toNum_(r.amount, r.feeType + ' amount', 0), currency: str_(r.currency), period: str_(r.period), notes: str_(r.notes) }; },
      function (r) { return createFee_(u, scope, r, meta); }, 'fee');
    // Scholarship
    var sch = null;
    if (!blank_(F.scId)) {
      sch = findBy_(S.SCHOLARSHIPS, 'id', F.scId);
      if (sch) {
        n += updateEntity_(S.SCHOLARSHIPS, sch, { name: need_(F.scName, 'Scholarship Name'), schType: str_(F.scType), mode: str_(F.scMode), eligibility: str_(F.scEligibility),
          minGpa: toNum_(F.scMinGpa, 'Minimum GPA', 0, 100), percent: toNum_(F.scPercent, 'Scholarship %', 0, 100), amount: toNum_(F.scAmount, 'Amount', 0),
          currency: str_(F.scCurrency), duration: str_(F.scDuration), renewal: str_(F.scRenewal) }, 'scholarship');
        sch = findBy_(S.SCHOLARSHIPS, 'id', F.scId);
      }
    } else if (!blank_(F.scName)) {
      sch = createScholarship_(u, scope, { name: F.scName, schType: F.scType, mode: F.scMode, eligibility: F.scEligibility, minGpa: F.scMinGpa, percent: F.scPercent,
        amount: F.scAmount, currency: F.scCurrency, duration: F.scDuration, renewal: F.scRenewal }, meta);
      added++; writeForm_({ scId: sch.id });
    }
    if (sch) {
      doGrid('tierGrid', S.TIERS, function (r) { return !blank_(r.tier); },
        function (r) { return { tier: r.tier, minGpa: toNum_(r.minGpa, 'Tier GPA', 0, 100), ielts: validateScore_('IELTS', r.ielts), toefl: validateScore_('TOEFL iBT', r.toefl),
          pte: validateScore_('PTE Academic', r.pte), topik: validateScore_('TOPIK', r.topik), otherTest: str_(r.otherTest), otherScore: str_(r.otherScore),
          percent: toNum_(r.percent, 'Tier %', 0, 100), amount: toNum_(r.amount, 'Tier amount', 0), scholarship: sch.name }; },
        function (r) { return createTier_(sch, r, meta); }, 'tier');
    }
    doGrid('docGrid', S.DOCUMENTS, function (r) { return !blank_(r.document); },
      function (r) { return { document: r.document, level: str_(r.level) || 'Mandatory', spec: str_(r.spec) }; },
      function (r) { return createDocument_(u, scope, r, meta); }, 'document');
    var country = findBy_(S.COUNTRIES, 'id', u.countryId);
    doGrid('csrGrid', S.CSR, function (r) { return !blank_(r.requirement); },
      function (r) { return { category: str_(r.category), requirement: r.requirement, details: str_(r.details), mandatory: str_(r.mandatory), uniId: r.scope === 'University' ? u.id : 'ALL', university: r.scope === 'University' ? u.name : '' }; },
      function (r) { return createCSR_(country, r.scope === 'University' ? u : null, r, meta); }, 'csr');
    doGrid('srcGrid', S.SOURCES, function (r) { return !blank_(r.url); },
      function (r) { return { sourceType: str_(r.sourceType), title: str_(r.title), url: r.url, checkedOn: toDate_(r.checkedOn, 'Last Checked') || '', checkedBy: str_(r.checkedBy) }; },
      function (r) { return createSource_(u, r, meta); }, 'source');

    // keep denormalised names in child tables in sync after a rename
    n += syncUniversityName_(u);
    afterChange_('UPDATE complete.\n\n' + n + ' field change(s) saved, ' + added + ' new record(s) added.' +
      (notes.length ? '\n\nNotes:\n' + notes.join('\n') : '') + '\n\nAll changes are recorded in 15_Audit_Log.', {}, gridIds);
  });
}

/** After a university rename/country change, update the copied names in child tables. */
function syncUniversityName_(u) {
  var S = UMS.SHEETS, count = 0;
  [S.CAMPUSES, S.INTAKES, S.ROUNDS, S.PROGRAMS, S.ACADEMIC, S.LANGUAGE, S.FEES, S.SCHOLARSHIPS, S.TIERS, S.DOCUMENTS, S.SOURCES].forEach(function (t) {
    var tab = readTable_(t);
    if (tab.idx.university === undefined) return;
    tab.objs.forEach(function (o) {
      if (o.uniId === u.id && (o.university !== u.name || o.country !== u.country)) {
        updateRow_(t, o._row, { university: u.name, country: u.country });
        count++;
      }
    });
  });
  return count ? 1 : 0;
}

/* ============================================================================
 *  CLEAR FORM
 * ========================================================================== */
function clearForm() {
  run_('Clear Form', function () {
    clearCards_(true);
    toast_('Form cleared. The record selected in ① is kept.', 'Clear Form', 4);
  }, { lock: false });
}
function clearCards_(withDefaults) {
  var L = deLayout_();
  var v = {};
  for (var k in L.fields) if (k.indexOf('ctx') !== 0 && k.indexOf('meta') !== 0) v[k] = '';
  if (withDefaults !== false) for (var d in L.defaults) if (d.indexOf('ctx') !== 0 && d.indexOf('meta') !== 0) v[d] = L.defaults[d];
  var gridKeys = Object.keys(L.grids);
  var g = {};
  if (withDefaults !== false) {
    g.feeGrid = DE_CARDS.filter(function (c) { return c.id === 'fees'; })[0].grid.prefill.feeType.map(function (t) { return { feeType: t }; });
  }
  writeForm_(v, g, gridKeys);
}

/* ============================================================================
 *  SEARCH BUTTONS
 * ========================================================================== */
function btnSearchUniversity() {
  run_('University Search', function () { refreshUsLists_(readControls_(UMS.SHEETS.US)); renderUniversitySearch_(); toast_('Search updated.', 'University Search', 3); }, { lock: false });
}
function btnClearUniversitySearch() {
  run_('Clear Filters', function () {
    ['country', 'university', 'campus', 'degree', 'intake', 'program'].forEach(function (k) { setControl_(UMS.SHEETS.US, k, ''); });
    refreshUsLists_({}); renderUniversitySearch_();
  }, { lock: false });
}
function btnSearchPrograms() {
  run_('Program Search', function () { refreshPsLists_(readControls_(UMS.SHEETS.PS)); renderProgramSearch_(); }, { lock: false });
}
function btnClearProgramSearch() {
  run_('Clear Filters', function () {
    Object.keys(controlLayout_(UMS.SHEETS.PS).pos).forEach(function (k) { setControl_(UMS.SHEETS.PS, k, ''); });
    setControl_(UMS.SHEETS.PS, 'scholarship', 'Any');
    refreshPsLists_({}); renderProgramSearch_();
  }, { lock: false });
}
function btnGenerateProfile() {
  run_('Print View', function () { renderPrintView_(); toast_('Profile generated. Print with File → Print (A4, Fit to width).', 'Print View', 5); }, { lock: false });
}

/** Saves the Print View profile as an A4 PDF in Google Drive and shows the link. */
function exportProfilePdf() {
  run_('Export PDF', function () {
    var f = readControls_(UMS.SHEETS.PV);
    var u = universityByName_(f.university, f.country);
    if (!u) throw new UmsError('Select a Country and University in Print View first.');
    renderPrintView_();
    SpreadsheetApp.flush();
    var ss = ss_(), sh = getSheet_(UMS.SHEETS.PV);
    var L = controlLayout_(UMS.SHEETS.PV);
    var lastRow = sh.getLastRow();
    var url = 'https://docs.google.com/spreadsheets/d/' + ss.getId() + '/export?format=pdf&size=A4&portrait=true&fitw=true' +
      '&gridlines=false&printtitle=false&sheetnames=false&pagenum=CENTER&fzr=false&top_margin=0.4&bottom_margin=0.4&left_margin=0.4&right_margin=0.4' +
      '&gid=' + sh.getSheetId() + '&r1=' + (L.resultsRow - 1) + '&c1=0&r2=' + lastRow + '&c2=' + (GRID.print.cols + 2);
    var resp = UrlFetchApp.fetch(url, { headers: { Authorization: 'Bearer ' + ScriptApp.getOAuthToken() }, muteHttpExceptions: true });
    if (resp.getResponseCode() !== 200) throw new UmsError('Google could not create the PDF (HTTP ' + resp.getResponseCode() + '). Use File → Print instead.');
    var name = 'University Profile - ' + u.name + ' - ' + Utilities.formatDate(new Date(), tz_(), 'yyyy-MM-dd') + '.pdf';
    var file = DriveApp.createFile(resp.getBlob().setName(name));
    writeAuditLog('EXPORT PDF', UMS.SHEETS.PV, u.id, u.country, u.name, '', '', file.getUrl());
    var html = HtmlService.createHtmlOutput('<div style="font-family:Arial;padding:8px"><p><b>' + name + '</b> was saved to your Google Drive.</p>' +
      '<p><a href="' + file.getUrl() + '" target="_blank" style="background:#1D4ED8;color:#fff;padding:10px 16px;border-radius:6px;text-decoration:none">Open PDF</a></p></div>')
      .setWidth(420).setHeight(160);
    SpreadsheetApp.getUi().showModalDialog(html, 'PDF ready');
  }, { lock: false });
}

/* ============================================================================
 *  NAVIGATION
 * ========================================================================== */
function navigateToSheet(name) {
  var sh = ss_().getSheetByName(name);
  if (!sh) { notify_('Sheet "' + name + '" not found. Run setup from the menu.'); return; }
  if (sh.isSheetHidden()) sh.showSheet();
  ss_().setActiveSheet(sh);
  sh.setActiveRange(sh.getRange(1, 1));
}
function navHome() { navigateToSheet(UMS.SHEETS.HOME); }
function navUniversitySearch() { navigateToSheet(UMS.SHEETS.US); }
function navProgramSearch() { navigateToSheet(UMS.SHEETS.PS); }
function navDataEntry() { navigateToSheet(UMS.SHEETS.DE); }
function navPrintView() { navigateToSheet(UMS.SHEETS.PV); }
function navDatabase() { navigateToSheet(UMS.SHEETS.UNIVERSITIES); }
function navSettings() { navigateToSheet(UMS.SHEETS.SETTINGS); }
function navAuditLog() { navigateToSheet(UMS.SHEETS.AUDIT); }

/* ============================================================================
 *  REFRESH
 * ========================================================================== */
function refreshDashboard() {
  run_('Refresh', function () {
    refreshDropdowns();
    renderHome_(false);
    renderUniversitySearch_();
    renderProgramSearch_();
    renderPrintView_();
    toast_('Dashboards and dropdowns refreshed.', 'Refresh', 3);
  }, { lock: false });
}

/* ============================================================================
 *  EVENTS
 * ========================================================================== */
function onOpen(e) {
  var ui = SpreadsheetApp.getUi();
  ui.createMenu('🎓 University System')
    .addItem('🏠 Home', 'navHome')
    .addItem('🔍 University Search', 'navUniversitySearch')
    .addItem('📚 Program Search', 'navProgramSearch')
    .addItem('✎ Data Entry', 'navDataEntry')
    .addItem('🖨 Print View', 'navPrintView')
    .addSeparator()
    .addSubMenu(ui.createMenu('＋ Add')
      .addItem('Country', 'addCountry').addItem('Visa Information', 'addVisaInformation').addItem('University', 'addUniversity')
      .addItem('Campus', 'addCampus').addItem('Intake', 'addIntake').addItem('Application Round', 'addApplicationRound')
      .addItem('Program', 'addProgram').addItem('Academic Requirement', 'addAcademicRequirement').addItem('Language Requirement', 'addLanguageRequirement')
      .addItem('Fee', 'addFee').addItem('Scholarship', 'addScholarship').addItem('Scholarship Tier', 'addScholarshipTier')
      .addItem('Document Requirement', 'addDocumentRequirement').addItem('Country-specific Requirement', 'addCountrySpecificRequirement')
      .addItem('Source', 'addSource'))
    .addSubMenu(ui.createMenu('✎ Update')
      .addItem('Load Record', 'btnLoadRecord').addItem('Update Record', 'updateRecord').addItem('Clear Form', 'clearForm'))
    .addSubMenu(ui.createMenu('🔍 Search & Print')
      .addItem('Run University Search', 'btnSearchUniversity').addItem('Run Program Search', 'btnSearchPrograms')
      .addItem('Generate Print Profile', 'btnGenerateProfile').addItem('Export Profile as PDF', 'exportProfilePdf'))
    .addSeparator()
    .addItem('↻ Refresh Dashboards & Dropdowns', 'refreshDashboard')
    .addSubMenu(ui.createMenu('⚙ Admin')
      .addItem('Open Database', 'navDatabase').addItem('Open Settings', 'navSettings').addItem('Open Audit Log', 'navAuditLog')
      .addSeparator()
      .addItem('Show database sheets', 'showDatabaseSheets').addItem('Hide database sheets', 'hideDatabaseSheets')
      .addItem('Apply protection (Settings → Protection Mode)', 'applyProtection')
      .addSeparator()
      .addItem('Remove SAMPLE / DEMO data', 'removeSampleData')
      .addItem('Run system self-test', 'runSelfTest')
      .addItem('Re-run setup (safe)', 'setupUniversityManagementSystem'))
    .addToUi();
}

/** Installable onOpen (runs as the owner): keeps HOME figures and dropdowns fresh. */
function handleOpen(e) {
  try { _CACHE = {}; refreshDropdowns(); renderHome_(false); } catch (err) { logError_('handleOpen', err); }
}

/** Installable onEdit: dependent dropdowns, auto-search, auto-stamping of manual database edits. */
function handleEdit(e) {
  if (!e || !e.range) return;
  var sh = e.range.getSheet(), name = sh.getName();
  var row = e.range.getRow(), col = e.range.getColumn();
  var single = e.range.getNumRows() === 1 && e.range.getNumColumns() === 1;
  _CACHE = {}; _SETTINGS = null;
  try {
    var auto = String(setting_('Auto Search On Edit')) !== 'No';
    if (name === UMS.SHEETS.US && single) {
      var k = controlAt_(name, row, col);
      if (!k) return;
      var clearKeys = { country: ['university', 'campus', 'intake', 'program'], university: ['campus', 'intake', 'program'], degree: ['program', 'intake'], campus: ['program'] }[k] || [];
      clearKeys.forEach(function (c) { setControl_(name, c, ''); });
      refreshUsLists_(readControls_(name));
      if (auto) renderUniversitySearch_();
    } else if (name === UMS.SHEETS.PS && single) {
      var k2 = controlAt_(name, row, col);
      if (!k2) return;
      var ck = { country: ['university', 'intake', 'major'], university: ['intake'] }[k2] || [];
      ck.forEach(function (c) { setControl_(name, c, ''); });
      refreshPsLists_(readControls_(name));
      if (auto) renderProgramSearch_();
    } else if (name === UMS.SHEETS.PV && single) {
      var k3 = controlAt_(name, row, col);
      if (!k3) return;
      if (k3 === 'country') { setControl_(name, 'university', ''); writeHelper_('PV_UNIV', universitiesOf_(readControls_(name).country)); }
      renderPrintView_();
    } else if (name === UMS.SHEETS.DE && single) {
      var L = deLayout_(), key = null;
      for (var f in L.fields) if (L.fields[f][0] + 1 === row && L.fields[f][1] + 1 === col) { key = f; break; }
      if (!key || key.indexOf('ctx') !== 0) return;
      var dk = { ctxCountry: ['ctxUniversity', 'ctxProgram', 'ctxIntake', 'ctxRound'], ctxUniversity: ['ctxProgram', 'ctxIntake', 'ctxRound'],
        ctxDegree: ['ctxProgram'], ctxIntake: ['ctxRound'] }[key] || [];
      if (dk.length) { var vv = {}; dk.forEach(function (x) { vv[x] = ''; }); writeForm_(vv); }
      refreshDeLists_(readForm_().fields);
      if (key === 'ctxCountry') writeForm_({ uniCountry: readForm_().fields.ctxCountry });
    } else if (SCHEMA[name] && name !== UMS.SHEETS.AUDIT && row > 1) {
      stampManualEdit_(e, sh, name);
    }
  } catch (err) {
    logError_('handleEdit', err);
    toast_(err && err.message ? err.message : String(err), 'Could not update', 6);
  }
}

/** Direct edits in a database sheet: stamp Last Updated / Updated By and log old → new. */
function stampManualEdit_(e, sh, name) {
  var t = readTable_(name);
  var header = t.headers[e.range.getColumn() - 1];
  if (['Last Updated', 'Updated By', 'Created Date'].indexOf(header) >= 0) return;
  var rows = e.range.getNumRows();
  if (rows > 200) return;
  var upd = t.idx.updated, by = t.idx.updatedBy, cr = t.idx.created;
  var now = new Date(), user = getUser_();
  for (var i = 0; i < rows; i++) {
    var r = e.range.getRow() + i;
    if (upd !== undefined) sh.getRange(r, upd + 1).setValue(now);
    if (by !== undefined) sh.getRange(r, by + 1).setValue(user);
    if (cr !== undefined && sh.getRange(r, cr + 1).getValue() === '') sh.getRange(r, cr + 1).setValue(now);
  }
  var idv = sh.getRange(e.range.getRow(), 1).getValue();
  writeAuditLog('MANUAL EDIT', name, idv, '', '', '', rows === 1 ? (e.oldValue === undefined ? '' : header + ': ' + e.oldValue) : '',
    rows === 1 ? header + ': ' + (e.value === undefined ? sh.getRange(e.range.getRow(), e.range.getColumn()).getValue() : e.value) : rows + ' rows edited in ' + e.range.getA1Notation());
}


/* ============================================================================
 *  ONE-CLICK SETUP  —  setupUniversityManagementSystem()
 *  Safe to run many times: creates only what is missing, never deletes data.
 * ========================================================================== */

function setupUniversityManagementSystem() {
  var t0 = Date.now();
  var lock = LockService.getDocumentLock();
  if (!lock.tryLock(30000)) { notify_('Setup is already running. Please wait.'); return; }
  _CACHE = {}; _SETTINGS = null; _LAYOUT_CACHE = {}; _DE_LAYOUT = null;
  var props = PropertiesService.getDocumentProperties();
  var resume = Number(props.getProperty('UMS_SETUP_RESUME') || 0);
  var resumeTs = Number(props.getProperty('UMS_SETUP_RESUME_TS') || 0);
  if (Date.now() - resumeTs > 3600000) resume = 0;
  var steps = [
    ['Creating sheets', function () { ensureAllSheets_(); }],
    ['Settings & dropdown lists', function () { setupSettings_(); }],
    ['Database tables', function () { setupDatabaseSheets_(); }],
    ['Helper lists', function () { setupHelperSheet_(); }],
    ['Sample data', function () { if (String(setting_('Sample Data Loaded')) !== 'Yes' && readTable_(UMS.SHEETS.UNIVERSITIES).objs.length === 0) { loadSampleData_(); setSetting_('Sample Data Loaded', 'Yes'); } }],
    ['Dropdown values', function () { refreshDropdowns(); }],
    ['HOME dashboard', function () { renderHome_(true); }],
    ['University Search', function () { drawSearchSheet_(UMS.SHEETS.US); renderUniversitySearch_(); }],
    ['Program Search', function () { drawSearchSheet_(UMS.SHEETS.PS); renderProgramSearch_(); }],
    ['Print View', function () { drawSearchSheet_(UMS.SHEETS.PV); renderPrintView_(); }],
    ['Data Entry form', function () { drawDataEntrySheet_(); }],
    ['Named ranges', function () { setupNamedRanges_(); }],
    ['Dropdown values (final)', function () { _CACHE = {}; refreshDropdowns(); }],
    ['Triggers', function () { setupTriggers_(); }],
    ['Protection & sheet order', function () { applyProtection_(); orderSheets_(); if (String(setting_('Hide Database Sheets')) !== 'No') hideDatabaseSheets_(); }]
  ];
  try {
    for (var i = 0; i < steps.length; i++) {
      if (i > 0 && i < resume && i !== 3) continue;               // resume after a time-out (always keep helper sheet)
      if (Date.now() - t0 > 300000) {
        props.setProperty('UMS_SETUP_RESUME', String(i));
        props.setProperty('UMS_SETUP_RESUME_TS', String(Date.now()));
        notify_('Setup is almost finished but Google limits one run to 6 minutes.\n\nPlease run  setupUniversityManagementSystem  ONE more time — it will continue from "' + steps[i][0] + '".', 'Setup — run once more');
        return;
      }
      toast_(steps[i][0] + ' …', 'Setting up (' + (i + 1) + '/' + steps.length + ')', 30);
      steps[i][1]();
      SpreadsheetApp.flush();
    }
    props.deleteProperty('UMS_SETUP_RESUME');
    setSetting_('Last Setup', new Date());
    setSetting_('Version', UMS.VERSION);
    writeAuditLog('SETUP', '', '', '', '', '', '', 'System setup completed (v' + UMS.VERSION + ') in ' + Math.round((Date.now() - t0) / 1000) + 's');
    navigateToSheet(UMS.SHEETS.HOME);
    notify_('✔ The University Management System is ready.\n\n• Reload this browser tab once to see the "🎓 University System" menu.\n' +
      '• Start on HOME. Click any card or navigation button.\n• Sample universities are marked "' + UMS.SAMPLE_FLAG + '" — remove them any time: menu → Admin → Remove SAMPLE / DEMO data.',
      'Setup complete');
  } catch (e) {
    logError_('setup', e);
    notify_('Setup stopped: ' + (e && e.message ? e.message : e) + '\n\nNo data was deleted. Fix the issue (if any) and run setup again.', 'Setup error');
    throw e;
  } finally {
    try { lock.releaseLock(); } catch (x) { }
  }
}

/* ---------------------------------------------------------------------------
 *  SHEETS
 * ------------------------------------------------------------------------- */
function allSheetNames_() {
  return UI_ORDER.concat([UMS.SHEETS.SETTINGS]).concat(DB_ORDER).concat([UMS.SHEETS.LISTS]);
}
function ensureAllSheets_() {
  var ss = ss_();
  // .xlsx files limit sheet names to 31 characters — restore the full name after import
  var legacy = { '16_Country_Specific_Requirement': UMS.SHEETS.CSR };
  for (var old in legacy) {
    var o = ss.getSheetByName(old);
    if (o && !ss.getSheetByName(legacy[old])) o.setName(legacy[old]);
  }
  allSheetNames_().forEach(function (n) { if (!ss.getSheetByName(n)) ss.insertSheet(n); });
  // the imported template's instruction sheet is no longer needed once setup runs
  var start = ss.getSheetByName('▶ START HERE');
  if (start && ss.getSheets().length > 1) ss.deleteSheet(start);
  // remove the default empty "Sheet1" only when it is completely empty
  ['Sheet1', 'Sheet 1'].forEach(function (n) {
    var s = ss.getSheetByName(n);
    if (s && s.getLastRow() === 0 && s.getLastColumn() === 0 && ss.getSheets().length > 1) ss.deleteSheet(s);
  });
}
function orderSheets_() {
  var ss = ss_();
  allSheetNames_().forEach(function (n, i) {
    var sh = ss.getSheetByName(n);
    if (!sh) return;
    var hidden = sh.isSheetHidden();
    if (hidden) sh.showSheet();
    ss.setActiveSheet(sh);
    ss.moveActiveSheet(i + 1);
    if (hidden) sh.hideSheet();
  });
  var tabs = {};
  tabs[UMS.SHEETS.HOME] = '#0F2A47'; tabs[UMS.SHEETS.US] = '#1D4ED8'; tabs[UMS.SHEETS.PS] = '#0F766E'; tabs[UMS.SHEETS.DE] = '#B45309'; tabs[UMS.SHEETS.PV] = '#6D28D9';
  for (var n in tabs) { var s = ss.getSheetByName(n); if (s) s.setTabColor(tabs[n]); }
  DB_ORDER.concat([UMS.SHEETS.SETTINGS, UMS.SHEETS.LISTS]).forEach(function (n) { var s = ss.getSheetByName(n); if (s) s.setTabColor('#94A3B8'); });
}

/* ---------------------------------------------------------------------------
 *  SETTINGS SHEET
 * ------------------------------------------------------------------------- */
function setupSettings_() {
  var sh = getSheet_(UMS.SHEETS.SETTINGS), C = UMS.C;
  var need = 3 + LISTS.length;
  if (sh.getMaxColumns() < need) sh.insertColumnsAfter(sh.getMaxColumns(), need - sh.getMaxColumns());
  sh.getRange(1, 1, 1, 2).setValues([['Setting', 'Value']]);
  var lr = Math.max(sh.getLastRow(), 1);
  var existing = sh.getRange(1, 1, lr, 1).getValues().map(function (r) { return String(r[0]).trim(); });
  var add = SETTINGS_DEFAULTS.filter(function (d) { return existing.indexOf(d[0]) < 0; });
  if (add.length) {
    var firstEmpty = 2;
    while (firstEmpty <= lr && String(sh.getRange(firstEmpty, 1).getValue()).trim() !== '') firstEmpty++;
    sh.getRange(firstEmpty, 1, add.length, 2).setValues(add);
  }
  // dropdown lists
  var maxLen = LISTS.reduce(function (a, l) { return Math.max(a, l[2].length); }, 0);
  ensureCapacity_(sh, 301);
  var head = sh.getRange(1, 4, 1, LISTS.length).getValues()[0];
  LISTS.forEach(function (l, i) {
    var col = 4 + i;
    var colVals = sh.getRange(2, col, 300, 1).getValues().filter(function (r) { return String(r[0]).trim() !== ''; });
    if (String(head[i]).trim() !== l[1]) sh.getRange(1, col).setValue(l[1]);
    if (!colVals.length) sh.getRange(2, col, l[2].length, 1).setValues(l[2].map(function (v) { return [v]; }));
  });
  // formatting
  var hdr = sh.getRange(1, 1, 1, need);
  hdr.setBackground(C.NAVY).setFontColor(C.WHITE).setFontWeight('bold').setFontFamily(UMS.FONT).setWrap(true).setVerticalAlignment('middle');
  sh.getRange(1, 3).setBackground(C.PAGE);
  sh.setRowHeight(1, 34);
  sh.setFrozenRows(1);
  sh.setColumnWidth(1, 250); sh.setColumnWidth(2, 220); sh.setColumnWidth(3, 24);
  sh.setColumnWidths(4, LISTS.length, 150);
  sh.getRange(2, 1, SETTINGS_DEFAULTS.length + 5, 1).setFontWeight('bold').setFontColor(C.NAVY);
  sh.getRange(2, 2, SETTINGS_DEFAULTS.length + 5, 1).setBackground(C.BLUE_XL);
  var helpRow = SETTINGS_DEFAULTS.length + 4;
  sh.getRange(helpRow, 1, 1, 2).breakApart().merge().setValue('ⓘ  Dropdown lists (columns D onward): add new values under any heading and every dropdown picks them up automatically. ' +
    'Do not rename the headings.').setWrap(true).setFontColor(C.MUTED).setFontStyle('italic').setFontWeight('normal').setBackground(C.PAGE);
  sh.setRowHeight(helpRow, 60);
  var yesNo = SpreadsheetApp.newDataValidation().requireValueInList(['Yes', 'No'], true).build();
  ['Auto Search On Edit', 'Hide Database Sheets', 'Sample Data Loaded'].forEach(function (k) {
    var r = settingRow_(sh, k); if (r) sh.getRange(r, 2).setDataValidation(yesNo);
  });
  var pr = settingRow_(sh, 'Protection Mode');
  if (pr) sh.getRange(pr, 2).setDataValidation(SpreadsheetApp.newDataValidation().requireValueInList(['Warning', 'Strict'], true).build());
  var ls = settingRow_(sh, 'Last Setup');
  if (ls) sh.getRange(ls, 2).setNumberFormat('yyyy-mm-dd hh:mm');
  _SETTINGS = null;
  invalidate_('__lists');
}
function settingRow_(sh, key) {
  var v = sh.getRange(1, 1, Math.max(sh.getLastRow(), 1), 1).getValues();
  for (var i = 0; i < v.length; i++) if (String(v[i][0]).trim() === key) return i + 1;
  return null;
}

/* ---------------------------------------------------------------------------
 *  DATABASE SHEETS
 * ------------------------------------------------------------------------- */
function ensureHeaders_(name) {
  var sh = getSheet_(name), schema = SCHEMA[name];
  var lc = sh.getLastColumn();
  var headers = lc ? sh.getRange(1, 1, 1, lc).getValues()[0].map(function (h) { return String(h).trim(); }) : [];
  if (!headers.filter(String).length) {
    if (sh.getMaxColumns() < schema.length) sh.insertColumnsAfter(sh.getMaxColumns(), schema.length - sh.getMaxColumns());
    sh.getRange(1, 1, 1, schema.length).setValues([schema.map(function (c) { return c[1]; })]);
    return;
  }
  var missing = schema.filter(function (c) { return headers.indexOf(c[1]) < 0; });
  if (missing.length) {
    var start = headers.length + 1;
    if (sh.getMaxColumns() < start + missing.length - 1) sh.insertColumnsAfter(sh.getMaxColumns(), start + missing.length - 1 - sh.getMaxColumns());
    sh.getRange(1, start, 1, missing.length).setValues([missing.map(function (c) { return c[1]; })]);
  }
  invalidate_(name);
}

function setupDatabaseSheets_() {
  var C = UMS.C;
  DB_ORDER.forEach(function (name) {
    ensureHeaders_(name);
    var sh = getSheet_(name), schema = SCHEMA[name];
    ensureCapacity_(sh, 500);
    var t = readTable_(name);
    var lc = t.headers.length, maxR = sh.getMaxRows();
    // extra (unused) columns are hidden from view but never deleted
    var hdr = sh.getRange(1, 1, 1, lc);
    hdr.setBackground(C.NAVY).setFontColor(C.WHITE).setFontWeight('bold').setFontFamily(UMS.FONT).setFontSize(10)
      .setWrap(true).setVerticalAlignment('middle').setHorizontalAlignment('left');
    sh.setRowHeight(1, 36);
    sh.setFrozenRows(1);
    sh.setFrozenColumns(name === UMS.SHEETS.AUDIT ? 0 : 1);
    sh.getRange(2, 1, maxR - 1, lc).setFontFamily(UMS.FONT).setFontSize(10).setVerticalAlignment('middle');
    schema.forEach(function (c) {
      var i = t.idx[c[0]];
      if (i === undefined) return;
      var col = i + 1, type = c[2] || 'text', h = c[1];
      var w = 140;
      if (c[0] === 'id') w = 170;
      else if (/Name|University|Program|Requirement|Scholarship$|Eligibility|Details|Specification|Title|Waiver|Background|Old Value|New Value/.test(h)) w = 220;
      else if (/Notes|Renewal|URL|Website|Financial/.test(h)) w = 230;
      else if (type === 'date') w = 110;
      else if (type === 'num') w = 100;
      sh.setColumnWidth(col, w);
      var body = sh.getRange(2, col, maxR - 1, 1);
      if (type === 'date') body.setNumberFormat(name === UMS.SHEETS.AUDIT ? 'yyyy-mm-dd hh:mm:ss' : 'yyyy-mm-dd').setHorizontalAlignment('center');
      if (type === 'num') body.setHorizontalAlignment('right');
      if (type.indexOf('list:') === 0) {
        var listKey = type.substring(5);
        body.setDataValidation(SpreadsheetApp.newDataValidation().requireValueInRange(ss_().getRange(listRangeA1_(listKey)), true)
          .setAllowInvalid(true).setHelpText('Choose from the list (edit lists in 01_Settings).').build());
      }
      if (type === 'url') body.setFontColor(C.BLUE);
      if (c[0] === 'id' || c[0] === 'uniId' || c[0] === 'countryId' || c[0] === 'intakeId' || c[0] === 'schId' || c[0] === 'programId') body.setFontColor('#334155').setFontFamily('Roboto Mono');
      if (c[0] === 'dataFlag') body.setFontColor(C.PURPLE).setFontWeight('bold');
    });
    // banding
    if (!sh.getBandings().length) {
      try {
        var band = sh.getRange(1, 1, maxR, lc).applyRowBanding(SpreadsheetApp.BandingTheme.LIGHT_GREY, true, false);
        band.setHeaderRowColor(C.NAVY).setFirstRowColor('#FFFFFF').setSecondRowColor('#F8FAFC');
      } catch (e) { Logger.log('banding: ' + e); }
    }
    // filter
    if (!sh.getFilter()) { try { sh.getRange(1, 1, maxR, lc).createFilter(); } catch (e2) { Logger.log('filter: ' + e2); } }
    // conditional formatting: status + deadlines
    var rules = sh.getConditionalFormatRules().filter(function (r) { return !isUmsRule_(r, sh); });
    if (t.idx.status !== undefined) {
      var srange = sh.getRange(2, t.idx.status + 1, maxR - 1, 1);
      for (var s in UMS.STATUS_STYLE) {
        rules.push(SpreadsheetApp.newConditionalFormatRule().whenTextEqualTo(s).setBackground(UMS.STATUS_STYLE[s].bg)
          .setFontColor(UMS.STATUS_STYLE[s].fc).setBold(true).setRanges([srange]).build());
      }
    }
    if (name === UMS.SHEETS.ROUNDS && t.idx.appEnd !== undefined) {
      var L = colLetter_(t.idx.appEnd + 1), rg = sh.getRange(2, t.idx.appEnd + 1, maxR - 1, 1);
      rules.push(SpreadsheetApp.newConditionalFormatRule().whenFormulaSatisfied('=AND(ISNUMBER($' + L + '2),$' + L + '2<TODAY())').setBackground('#FEE2E2').setFontColor('#991B1B').setRanges([rg]).build());
      rules.push(SpreadsheetApp.newConditionalFormatRule().whenFormulaSatisfied('=AND(ISNUMBER($' + L + '2),$' + L + '2-TODAY()<=7)').setBackground('#FFEDD5').setFontColor('#9A3412').setRanges([rg]).build());
      rules.push(SpreadsheetApp.newConditionalFormatRule().whenFormulaSatisfied('=AND(ISNUMBER($' + L + '2),$' + L + '2-TODAY()<=30)').setBackground('#FEF9C3').setFontColor('#854D0E').setRanges([rg]).build());
    }
    if (name === UMS.SHEETS.AUDIT && t.idx.action !== undefined) {
      var ar = sh.getRange(2, t.idx.action + 1, maxR - 1, 1);
      rules.push(SpreadsheetApp.newConditionalFormatRule().whenTextEqualTo('ERROR').setBackground('#FEE2E2').setFontColor('#991B1B').setBold(true).setRanges([ar]).build());
      rules.push(SpreadsheetApp.newConditionalFormatRule().whenTextEqualTo('ADD').setFontColor(C.GREEN).setBold(true).setRanges([ar]).build());
      rules.push(SpreadsheetApp.newConditionalFormatRule().whenTextEqualTo('UPDATE').setFontColor(C.AMBER).setBold(true).setRanges([ar]).build());
    }
    sh.setConditionalFormatRules(rules);
  });
}
/** A rule belongs to UMS if it targets a single column starting at row 2 (the ones we create). */
function isUmsRule_(rule, sh) {
  try {
    var rs = rule.getRanges();
    return rs.length === 1 && rs[0].getRow() === 2 && rs[0].getNumColumns() === 1 && rs[0].getNumRows() >= sh.getMaxRows() - 2;
  } catch (e) { return false; }
}

/* ---------------------------------------------------------------------------
 *  HELPER SHEET
 * ------------------------------------------------------------------------- */
function setupHelperSheet_() {
  var sh = getSheet_(UMS.SHEETS.LISTS);
  var keys = Object.keys(HELPER);
  var W = keys.length;
  if (sh.getMaxColumns() < W) sh.insertColumnsAfter(sh.getMaxColumns(), W - sh.getMaxColumns());
  ensureCapacity_(sh, HELPER_ROWS + 1);
  var head = [];
  keys.forEach(function (k) { head[HELPER[k] - 1] = k; });
  sh.getRange(1, 1, 1, W).setValues([head]).setFontWeight('bold').setBackground('#E2E8F0');
  sh.setFrozenRows(1);
  sh.getRange('A1').setNote('Technical sheet — rebuilt automatically for dependent dropdowns. Do not edit.');
  sh.hideSheet();
}

/* ---------------------------------------------------------------------------
 *  DASHBOARD DRAWING (preserves what users have typed in the controls)
 * ------------------------------------------------------------------------- */
function drawSearchSheet_(name) {
  var sh = getSheet_(name);
  var saved = {};
  try { saved = readControls_(name); } catch (e) { saved = {}; }
  var L = buildSearchChrome_(name);
  _LAYOUT_CACHE[name] = L;
  removeButtons_(sh);
  clearRegion_(sh, 1);
  sizeColumns_(sh, CONTROLS[name].mode);
  L.canvas.flush(sh, 1);
  sh.setFrozenRows(5);
  placeButtons_(sh, L.canvas, 1);
  var keys = Object.keys(L.pos);
  keys.forEach(function (k) { if (!blank_(saved[k])) setControl_(name, k, saved[k]); });
  if (name === UMS.SHEETS.PS && blank_(saved.scholarship)) setControl_(name, 'scholarship', 'Any');
  if (name === UMS.SHEETS.US && blank_(saved.country) && blank_(saved.university)) {
    // open on the first sample university so the dashboard looks alive after setup
    var u = readTable_(UMS.SHEETS.UNIVERSITIES).objs[0];
    if (u) { setControl_(name, 'country', u.country); setControl_(name, 'university', u.name); refreshUsLists_(readControls_(name)); }
  }
  if (name === UMS.SHEETS.PV && blank_(saved.university)) {
    var u2 = readTable_(UMS.SHEETS.UNIVERSITIES).objs[0];
    if (u2) { setControl_(name, 'country', u2.country); setControl_(name, 'university', u2.name); writeHelper_('PV_UNIV', universitiesOf_(u2.country)); }
  }
}

function drawDataEntrySheet_() {
  var sh = getSheet_(UMS.SHEETS.DE);
  var saved = null;
  try { if (sh.getLastRow() > 20) saved = readForm_(); } catch (e) { saved = null; }
  _DE_LAYOUT = buildDataEntry_();
  var L = _DE_LAYOUT;
  removeButtons_(sh);
  clearRegion_(sh, 1);
  sizeColumns_(sh, 'wide');
  L.canvas.flush(sh, 1);
  finishPage_(sh, L.canvas.rowCount(), 'wide');
  sh.setFrozenRows(5);
  placeButtons_(sh, L.canvas, 1);
  if (saved && Object.keys(saved.fields).some(function (k) { return !blank_(saved.fields[k]); })) {
    writeForm_(saved.fields, saved.grids);
  } else {
    var v = {};
    for (var d in L.defaults) v[d] = L.defaults[d];
    var fees = DE_CARDS.filter(function (c) { return c.id === 'fees'; })[0].grid.prefill.feeType.map(function (t) { return { feeType: t }; });
    writeForm_(v, { feeGrid: fees });
  }
}

/* ---------------------------------------------------------------------------
 *  NAMED RANGES
 * ------------------------------------------------------------------------- */
function setupNamedRanges_() {
  var ss = ss_();
  var existing = {};
  ss.getNamedRanges().forEach(function (nr) { existing[nr.getName()] = nr; });
  var setNR = function (name, range) {
    try {
      if (existing[name]) existing[name].setRange(range);
      else ss.setNamedRange(name, range);
    } catch (e) { Logger.log('Named range ' + name + ': ' + e); }
  };
  for (var key in UMS.SHEETS) {
    var n = UMS.SHEETS[key];
    if (DB_ORDER.indexOf(n) < 0) continue;
    var sh = getSheet_(n), lc = Math.max(1, readTable_(n).headers.length);
    setNR('DB_' + key, sh.getRange(1, 1, sh.getMaxRows(), lc));
  }
  LISTS.forEach(function (l) { setNR('LIST_' + l[0], ss.getRange(listRangeA1_(l[0]))); });
  var prefix = {}; prefix[UMS.SHEETS.US] = 'US_'; prefix[UMS.SHEETS.PS] = 'PS_'; prefix[UMS.SHEETS.PV] = 'PV_';
  for (var sn in prefix) {
    var L = controlLayout_(sn), sh2 = getSheet_(sn);
    for (var k in L.pos) setNR(prefix[sn] + k.toUpperCase(), sh2.getRange(L.pos[k][0] + 1, L.pos[k][1] + 1));
  }
  var D = deLayout_(), sh3 = getSheet_(UMS.SHEETS.DE);
  for (var f in D.fields) setNR('DE_' + f.toUpperCase(), sh3.getRange(D.fields[f][0] + 1, D.fields[f][1] + 1));
  for (var g in D.grids) {
    var G = D.grids[g];
    setNR('DE_' + g.toUpperCase(), sh3.getRange(G.r0 + 1, 2, G.rows, 12));
  }
}

/* ---------------------------------------------------------------------------
 *  TRIGGERS
 * ------------------------------------------------------------------------- */
function setupTriggers_() {
  var have = {};
  ScriptApp.getProjectTriggers().forEach(function (t) { have[t.getHandlerFunction()] = true; });
  var ss = ss_();
  if (!have.handleEdit) ScriptApp.newTrigger('handleEdit').forSpreadsheet(ss).onEdit().create();
  if (!have.handleOpen) ScriptApp.newTrigger('handleOpen').forSpreadsheet(ss).onOpen().create();
}

/* ---------------------------------------------------------------------------
 *  PROTECTION
 *   Warning (default): anyone editing a backend sheet by hand gets a warning;
 *                      the dashboards & buttons work for every editor.
 *   Strict: only the owner + Admin Emails can edit backend sheets.
 *           (Other users can still search; their ＋ADD buttons will fail.)
 * ------------------------------------------------------------------------- */
function applyProtection() { run_('Apply Protection', function () { applyProtection_(); notify_('Protection applied (' + (setting_('Protection Mode') || 'Warning') + ' mode).'); }); }

function applyProtection_() {
  var ss = ss_();
  var strict = String(setting_('Protection Mode')) === 'Strict';
  var admins = String(setting_('Admin Emails (comma separated)') || '').split(/[,;\s]+/).filter(function (x) { return x.indexOf('@') > 0; });
  var backend = DB_ORDER.concat([UMS.SHEETS.SETTINGS, UMS.SHEETS.LISTS]);
  backend.forEach(function (n) {
    var sh = ss.getSheetByName(n); if (!sh) return;
    var p = first_(sh.getProtections(SpreadsheetApp.ProtectionType.SHEET), function (x) { return x.getDescription().indexOf('UMS') === 0; });
    if (!p) p = sh.protect().setDescription('UMS backend — edit through the Data Entry dashboard');
    if (strict) {
      p.setWarningOnly(false);
      var me = Session.getEffectiveUser();
      p.addEditor(me);
      if (admins.length) p.addEditors(admins);
      var keep = [String(me.getEmail())].concat(admins);
      var remove = p.getEditors().filter(function (u) { return keep.indexOf(u.getEmail()) < 0; });
      if (remove.length) p.removeEditors(remove);
      if (p.canDomainEdit()) p.setDomainEdit(false);
    } else {
      p.setWarningOnly(true);
    }
  });
  // Dashboards: warn when someone types outside the input boxes
  UI_ORDER.forEach(function (n) {
    var sh = ss.getSheetByName(n); if (!sh) return;
    var p = first_(sh.getProtections(SpreadsheetApp.ProtectionType.SHEET), function (x) { return x.getDescription().indexOf('UMS') === 0; });
    if (!p) p = sh.protect().setDescription('UMS dashboard layout');
    p.setWarningOnly(true);
    var inputs = [];
    if (CONTROLS[n]) { var L = controlLayout_(n); for (var k in L.pos) inputs.push(sh.getRange(L.pos[k][0] + 1, L.pos[k][1] + 1)); }
    if (n === UMS.SHEETS.DE) {
      var D = deLayout_();
      for (var f in D.fields) inputs.push(sh.getRange(D.fields[f][0] + 1, D.fields[f][1] + 1));
      for (var g in D.grids) inputs.push(sh.getRange(D.grids[g].r0 + 1, 2, D.grids[g].rows, 12));
    }
    if (inputs.length) p.setUnprotectedRanges(inputs);
  });
}

function hideDatabaseSheets() { run_('Hide database sheets', function () { hideDatabaseSheets_(); toast_('Database sheets hidden. Use the DATABASE button to open them.', 'Admin', 4); }, { lock: false }); }
function hideDatabaseSheets_() {
  var ss = ss_();
  var home = ss.getSheetByName(UMS.SHEETS.HOME);
  if (home) ss.setActiveSheet(home);
  DB_ORDER.concat([UMS.SHEETS.SETTINGS, UMS.SHEETS.LISTS]).forEach(function (n) {
    var sh = ss.getSheetByName(n);
    if (sh && !sh.isSheetHidden()) sh.hideSheet();
  });
}
function showDatabaseSheets() {
  run_('Show database sheets', function () {
    DB_ORDER.concat([UMS.SHEETS.SETTINGS]).forEach(function (n) { var sh = ss_().getSheetByName(n); if (sh && sh.isSheetHidden()) sh.showSheet(); });
  }, { lock: false });
}

/* ============================================================================
 *  SAMPLE / DEMO DATA  — clearly labelled, NOT real admission information
 * ========================================================================== */
function loadSampleData_() {
  var today = today_();
  var dd = function (n) { var d = new Date(today.getTime()); d.setDate(d.getDate() + n); return d; };
  var yr = today.getFullYear() + 1;
  var ref = { status: 'Verified', dataFlag: '' };
  var SAMPLE = { status: 'Pending Verification', dataFlag: UMS.SAMPLE_FLAG };
  var note = 'SAMPLE / DEMO DATA — invented for demonstration. Replace with information from the official university website.';

  [['South Korea', 'KR', 'East Asia', 'KRW', 'Korean'], ['Australia', 'AU', 'Oceania', 'AUD', 'English'], ['Canada', 'CA', 'North America', 'CAD', 'English, French'],
   ['United Kingdom', 'UK', 'Europe', 'GBP', 'English'], ['USA', 'US', 'North America', 'USD', 'English'], ['Malaysia', 'MY', 'South-East Asia', 'MYR', 'Malay, English'],
   ['Japan', 'JP', 'East Asia', 'JPY', 'Japanese'], ['New Zealand', 'NZ', 'Oceania', 'NZD', 'English, Māori'], ['Germany', 'DE', 'Europe', 'EUR', 'German'],
   ['Ireland', 'IE', 'Europe', 'EUR', 'English, Irish']].forEach(function (c) {
    if (!countryByName_(c[0])) createCountry_({ country: c[0], id: c[1], region: c[2], currency: c[3], language: c[4], active: 'Yes', notes: 'Reference data (country, currency, language).' }, ref);
  });

  var unis = [
    { country: 'South Korea', name: 'Hanbit Institute of Science & Technology (SAMPLE)', city: 'Seoul', type: 'National', slug: 'hanbit', ranking: 'SAMPLE — not ranked', accreditation: 'SAMPLE accreditation',
      intakes: [{ term: 'Spring', year: yr, rounds: [['Round 1', -30, 5, 12, 14, 30], ['Round 2', 8, 22, 29, 31, 45], ['Round 3', 35, 55, 60, 62, 75]] },
                { term: 'Fall', year: yr, rounds: [['Round 1', 150, 190, 200, 205, 220]] }],
      programs: [["Bachelor's", 'B.Eng. Computer Science & Engineering', 'Computer Science', 'Engineering', 'Computer Science', '4 years', 'English', 'Spring; Fall'],
                 ["Master's", 'M.S. Mechanical Engineering', 'Mechanical Engineering', 'Engineering', 'Mechanical Engineering', '2 years', 'English', 'Spring; Fall'],
                 ["Master's", 'M.A. International Business', 'Business Administration', 'Business', 'Business', '2 years', 'Korean', 'Spring']],
      acad: { minGpa: 3.0, gpaScale: '4.5', prevDegree: "Bachelor's degree (4 years)", background: 'Related field (SAMPLE)' },
      lang: [['IELTS', 5.5, '', 'Native English speakers (SAMPLE)', "Master's"], ['TOEFL iBT', 71, '', '', "Master's"], ['TOPIK', 3, '', 'Required for Korean-taught programs (SAMPLE)', "Master's"], ['TOPIK', 3, '', '', "Bachelor's"], ['IELTS', 5.5, '', '', "Bachelor's"]],
      fees: [['Application Fee', 100000, 'KRW', 'One-time', 'All'], ['Admission Fee', 900000, 'KRW', 'One-time', 'All'], ['Tuition Fee', 4200000, 'KRW', 'Per Semester', "Bachelor's"],
             ['Tuition Fee', 5100000, 'KRW', 'Per Semester', "Master's"], ['Insurance', 150000, 'KRW', 'Per Semester', 'All']],
      sch: { name: 'International Merit Scholarship (SAMPLE)', schType: 'Percentage', mode: 'Automatic', eligibility: 'Admitted international students (SAMPLE)', percent: 30, duration: 'First semester, renewable',
        renewal: 'Semester GPA 3.0 / 4.5 (SAMPLE)', tiers: [['Tier A', '', 7.0, 94, '', 5, 100], ['Tier B', '', 6.5, 85, '', 4, 50], ['Tier C', '', 6.0, 79, '', 3, 30]] },
      csr: [['Document Attestation', 'Apostille or consular confirmation of degree certificates (SAMPLE)', 'Verify the current rule on the official source.'],
            ['Financial', 'Bank balance certificate in student\'s or sponsor\'s name (SAMPLE)', 'Amount and validity period must be verified.']],
      visa: ['Student Visa (SAMPLE)', 'Proof of funds — verify amount (SAMPLE)', 'Verify (SAMPLE)', 'Part-time work limits apply — verify (SAMPLE)'] },
    { country: 'Australia', name: 'Coral Coast University (SAMPLE)', city: 'Brisbane', type: 'Public', slug: 'coralcoast', ranking: 'SAMPLE — not ranked', accreditation: 'SAMPLE accreditation',
      intakes: [{ term: 'February', year: yr, rounds: [['Regular Application', -60, 45, '', '', 70]] }, { term: 'July', year: yr, rounds: [['Regular Application', 60, 200, '', '', 220]] }],
      programs: [["Bachelor's", 'Bachelor of Information Technology', 'Information Technology', 'Science & Engineering', 'Information Technology', '3 years', 'English', 'February; July'],
                 ["Master's", 'Master of Data Science', 'Computer Science', 'Science & Engineering', 'Data Science', '2 years', 'English', 'February; July'],
                 ["Master's", 'Master of Professional Accounting', 'Accounting', 'Business', 'Accounting', '2 years', 'English', 'February']],
      acad: { minGpa: 2.8, gpaScale: '4.0', prevDegree: "Bachelor's degree", background: 'Any discipline (SAMPLE)' },
      lang: [['IELTS', 6.5, 'No band below 6.0 (SAMPLE)', '', 'All'], ['PTE Academic', 58, 'No section below 50 (SAMPLE)', '', 'All'], ['TOEFL iBT', 79, '', '', 'All']],
      fees: [['Application Fee', 100, 'AUD', 'One-time', 'All'], ['Tuition Fee', 38000, 'AUD', 'Per Year', "Bachelor's"], ['Tuition Fee', 42000, 'AUD', 'Per Year', "Master's"], ['Deposit', 10000, 'AUD', 'One-time', 'All']],
      sch: { name: 'Global Excellence Scholarship (SAMPLE)', schType: 'Percentage', mode: 'Application Required', eligibility: 'GPA 3.2+ (SAMPLE)', minGpa: 3.2, percent: 20, duration: 'Full program', renewal: 'Maintain credit average (SAMPLE)', tiers: [] },
      csr: [['Visa', 'Genuine Student statement (SAMPLE)', 'Verify the current requirement on the official government source.'], ['Health / Insurance', 'Overseas Student Health Cover for visa duration (SAMPLE)', '']],
      visa: ['Student Visa (SAMPLE)', 'Evidence of funds — verify (SAMPLE)', 'Verify (SAMPLE)', 'Verify (SAMPLE)'] },
    { country: 'Canada', name: 'Northern Lakes University (SAMPLE)', city: 'Toronto', type: 'Public', slug: 'northernlakes', ranking: 'SAMPLE — not ranked', accreditation: 'SAMPLE accreditation',
      intakes: [{ term: 'September', year: yr, rounds: [['Early Application', -90, -3, '', '', 20], ['Regular Application', -2, 70, '', '', 110]] }, { term: 'January', year: yr + 1, rounds: [['Regular Application', 120, 240, '', '', 260]] }],
      programs: [["Bachelor's", 'B.Sc. Environmental Science', 'Environmental Science', 'Science', 'Environmental Science', '4 years', 'English', 'September'],
                 ["Master's", 'M.Eng. Mechanical Engineering', 'Mechanical Engineering', 'Engineering', 'Mechanical Engineering', '2 years', 'English', 'September; January'],
                 ["Master's", 'MBA', 'Business Administration', 'Business', 'Business', '16 months', 'English', 'September']],
      acad: { minGpa: 3.0, gpaScale: '4.0', prevDegree: "Four-year Bachelor's degree", background: 'Relevant field (SAMPLE)', workExp: 'MBA: 2 years (SAMPLE)' },
      lang: [['IELTS', 6.5, 'No band below 6.0 (SAMPLE)', '', 'All'], ['TOEFL iBT', 90, '', '', 'All'], ['Duolingo', 115, '', '', 'All']],
      fees: [['Application Fee', 150, 'CAD', 'One-time', 'All'], ['Tuition Fee', 36000, 'CAD', 'Per Year', "Bachelor's"], ['Tuition Fee', 1450, 'CAD', 'Per Credit', "Master's"], ['Student Service Fee', 950, 'CAD', 'Per Year', 'All']],
      sch: { name: 'International Entrance Award (SAMPLE)', schType: 'Fixed Amount', mode: 'Automatic', eligibility: 'Admission average 85%+ (SAMPLE)', amount: 5000, currency: 'CAD', duration: 'One-time', renewal: 'Not renewable (SAMPLE)', tiers: [] },
      csr: [['Visa', 'Provincial Attestation Letter (SAMPLE)', 'Verify the current requirement on the official government source.'], ['Financial', 'Proof of funds for tuition + living costs (SAMPLE)', '']],
      visa: ['Study Permit (SAMPLE)', 'Proof of funds — verify amount (SAMPLE)', 'Verify (SAMPLE)', 'Verify (SAMPLE)'] },
    { country: 'United Kingdom', name: 'Kingsbridge University (SAMPLE)', city: 'Manchester', type: 'Public', slug: 'kingsbridge', ranking: 'SAMPLE — not ranked', accreditation: 'SAMPLE accreditation',
      intakes: [{ term: 'September', year: yr, rounds: [['Rolling Admission', -20, 120, '', '', 150]] }, { term: 'January', year: yr, rounds: [['Regular Application', -40, 16, '', '', 40]] }],
      programs: [["Bachelor's", 'BSc (Hons) Business Management', 'Business School', 'Business', 'Business', '3 years', 'English', 'September'],
                 ["Master's", 'MSc Artificial Intelligence', 'Computer Science', 'Engineering', 'Artificial Intelligence', '1 year', 'English', 'September; January'],
                 ["Master's", 'MSc Public Health', 'Public Health', 'Health Sciences', 'Public Health', '1 year', 'English', 'September']],
      acad: { minGpa: 3.0, gpaScale: '4.0', prevDegree: "Bachelor's (equivalent to UK 2:2) (SAMPLE)", background: 'Relevant subject (SAMPLE)' },
      lang: [['IELTS', 6.5, 'No component below 6.0 (SAMPLE)', 'Previous degree taught in English (SAMPLE)', 'All'], ['PTE Academic', 62, '', '', 'All']],
      fees: [['Application Fee', 0, 'GBP', 'One-time', 'All'], ['Tuition Fee', 21000, 'GBP', 'Per Year', "Bachelor's"], ['Tuition Fee', 22500, 'GBP', 'Total Program', "Master's"], ['Deposit', 2000, 'GBP', 'One-time', 'All']],
      sch: { name: 'International Welcome Bursary (SAMPLE)', schType: 'Fixed Amount', mode: 'Automatic', eligibility: 'Self-funded international students (SAMPLE)', amount: 3000, currency: 'GBP', duration: 'First year', renewal: '',
        tiers: [['GPA 3.5+', 3.5, '', '', '', '', '', 5000], ['GPA 3.0 – 3.49', 3.0, '', '', '', '', '', 3000]] },
      csr: [['Visa', 'CAS — Confirmation of Acceptance for Studies (SAMPLE)', 'Issued after deposit — verify.'], ['Health / Insurance', 'TB test certificate where required (SAMPLE)', 'Verify on the official government source.']],
      visa: ['Student Visa (SAMPLE)', 'Maintenance funds — verify (SAMPLE)', 'Verify (SAMPLE)', 'Verify (SAMPLE)'] }
  ];

  var docs = [['Passport', 'Mandatory', 'Valid 6+ months (SAMPLE)'], ['Academic Certificate', 'Mandatory', 'Attested copy (SAMPLE)'], ['Transcript', 'Mandatory', ''],
    ['IELTS / English Test Report', 'Conditional', 'Unless waived (SAMPLE)'], ['Statement of Purpose (SOP)', 'Mandatory', '500–1000 words (SAMPLE)'],
    ['Letter of Recommendation (LOR)', 'Mandatory', '2 academic referees (SAMPLE)'], ['CV / Resume', 'Optional', ''], ['Bank Statement', 'Mandatory', 'Verify amount (SAMPLE)']];

  unis.forEach(function (s) {
    var u = createUniversity_({ country: s.country, name: s.name, city: s.city, type: s.type, website: 'https://example.edu/' + s.slug,
      intlWebsite: 'https://example.edu/' + s.slug + '/international', ranking: s.ranking, accreditation: s.accreditation, notes: note }, SAMPLE);
    createCampus_(u, { campus: 'Main Campus', city: s.city, address: 'SAMPLE address' }, SAMPLE);
    s.intakes.forEach(function (it) {
      var intake = createIntake_(u, { term: it.term, year: it.year, intakeType: 'Main Intake', degree: 'All', rolling: it.rounds[0][0] === 'Rolling Admission' ? 'Yes' : 'No', notes: note }, SAMPLE);
      it.rounds.forEach(function (r) {
        createRound_(intake, { round: r[0], appStart: dd(r[1]), appEnd: dd(r[2]), interviewStart: r[3] === '' ? '' : dd(r[3]), interviewEnd: r[4] === '' ? '' : dd(r[4]),
          result: dd(r[5]), notes: note }, SAMPLE);
      });
    });
    var progs = s.programs.map(function (p) {
      return createProgram_(u, { degree: p[0], program: p[1], department: p[2], faculty: p[3], major: p[4], duration: p[5], language: p[6], intakes: p[7], campus: 'Main Campus', notes: note }, SAMPLE);
    });
    var master = progs.filter(function (p) { return p.degree === "Master's"; });
    master.forEach(function (p) { createAcademic_(u, { programId: p.id, program: p.program, degree: p.degree }, s.acad, SAMPLE); });
    createAcademic_(u, { programId: 'ALL', program: '', degree: "Bachelor's" }, { minPercent: 60, prevDegree: 'Higher Secondary (12 years) (SAMPLE)', other: 'SAMPLE requirement' }, SAMPLE);
    s.lang.forEach(function (l) { createLanguage_(u, { programId: 'ALL', program: '', degree: l[4] }, { test: l[0], minScore: l[1], subScore: l[2], waiver: l[3] }, SAMPLE); });
    s.fees.forEach(function (f) { createFee_(u, { programId: 'ALL', program: '', degree: f[4] }, { feeType: f[0], amount: f[1], currency: f[2], period: f[3], notes: 'SAMPLE' }, SAMPLE); });
    var sch = createScholarship_(u, { programId: 'ALL', program: '', degree: 'All' }, s.sch, SAMPLE);
    s.sch.tiers.forEach(function (t) {
      createTier_(sch, { tier: t[0], minGpa: t[1], ielts: t[2], toefl: t[3], pte: t[4], topik: t[5], percent: t.length > 6 && s.sch.schType === 'Percentage' ? t[6] : '',
        amount: s.sch.schType === 'Percentage' ? '' : t[7] }, SAMPLE);
    });
    docs.forEach(function (d) { createDocument_(u, { programId: 'ALL', program: '', degree: 'All' }, { document: d[0], level: d[1], spec: d[2] }, SAMPLE); });
    var country = countryByName_(s.country);
    s.csr.forEach(function (c) { createCSR_(country, null, { category: c[0], requirement: c[1], details: c[2], mandatory: 'Yes' }, SAMPLE); });
    if (!checkDuplicate('visa', { countryId: country.id, visaType: s.visa[0] })) {
      createVisa_(country, { visaType: s.visa[0], financial: s.visa[1], processing: s.visa[2], workRights: s.visa[3], notes: note }, SAMPLE);
    }
    createSource_(u, { sourceType: 'Admission Page', title: 'Admissions (SAMPLE)', url: 'https://example.edu/' + s.slug + '/admissions', checkedBy: 'Demo' }, SAMPLE);
    createSource_(u, { sourceType: 'Scholarship Page', title: 'Scholarships (SAMPLE)', url: 'https://example.edu/' + s.slug + '/scholarships', checkedBy: 'Demo' }, SAMPLE);
  });
  _CACHE = {};
}

/** Deletes every row flagged "SAMPLE / DEMO DATA" (real data is untouched). */
function removeSampleData() {
  if (!confirm_('Delete ALL records marked "' + UMS.SAMPLE_FLAG + '"?\n\nYour own records are not affected.', 'Remove sample data')) return;
  run_('Remove sample data', function () {
    var total = 0;
    DB_ORDER.forEach(function (n) { total += removeFlagged_(n, 'SAMPLE'); });
    writeAuditLog('DELETE SAMPLE', '', '', '', '', '', '', total + ' sample rows removed');
    _CACHE = {};
    refreshDropdowns();
    ['country', 'university', 'campus', 'intake', 'program', 'degree'].forEach(function (k) { setControl_(UMS.SHEETS.US, k, ''); });
    setControl_(UMS.SHEETS.PV, 'university', '');
    renderHome_(false); renderUniversitySearch_(); renderProgramSearch_(); renderPrintView_();
    notify_(total + ' sample rows removed. The system is now empty and ready for your real data.\n\n(The 10 reference countries stay — they are not sample data.)', 'Done');
  });
}
function removeFlagged_(name, needle) {
  var t = readTable_(name);
  if (t.idx.dataFlag === undefined || t.lastRow < 2) return 0;
  var sh = t.sheet, lc = t.headers.length;
  var vals = sh.getRange(2, 1, t.lastRow - 1, lc).getValues();
  var keep = vals.filter(function (r) { return String(r[t.idx.dataFlag]).indexOf(needle) < 0; });
  var removed = vals.length - keep.length;
  if (!removed) return 0;
  sh.getRange(2, 1, vals.length, lc).clearContent();
  if (keep.length) sh.getRange(2, 1, keep.length, lc).setValues(keep);
  invalidate_(name);
  return removed;
}

/* ============================================================================
 *  SELF-TEST (menu → Admin → Run system self-test)
 *  Creates a temporary test country + university, exercises every function,
 *  then deletes the test rows.  Your data is not touched.
 * ========================================================================== */
function runSelfTest() {
  run_('Self-test', function () {
    var res = selfTest_();
    notify_(res.join('\n'), 'Self-test results');
  });
}
function selfTest_() {
  var out = [], META = { status: 'Pending Verification', dataFlag: 'SELFTEST' };
  var ok = function (name, fn) {
    try { var r = fn(); out.push('✔ ' + name + (r ? ' — ' + r : '')); }
    catch (e) { out.push('✘ ' + name + ' — ' + (e && e.message ? e.message.split('\n')[0] : e)); }
  };
  var country, u, p, it, sch;
  try {
    ok('1  Add country', function () { country = createCountry_({ country: 'Testland', id: 'ZT', region: 'Other', currency: 'USD' }, META); return country.id; });
    ok('2  Add university', function () { u = createUniversity_({ country: 'Testland', name: 'Selftest University', city: 'Test City' }, META); return u.id; });
    ok('3  Add campus', function () { return createCampus_(u, { campus: 'North Campus' }, META).id; });
    ok('4  Add program', function () { p = createProgram_(u, { degree: "Master's", program: 'Test Mechanical Engineering', major: 'Mechanical Engineering', language: 'English' }, META); return p.id; });
    ok('5  Add intake', function () { it = createIntake_(u, { term: 'Spring', year: today_().getFullYear() + 1 }, META); return it.id; });
    ok('6  Add application round', function () { var d = new Date(); d.setDate(d.getDate() + 10); return createRound_(it, { round: 'Round 1', appEnd: d }, META).id; });
    ok('7  Add language requirement', function () { return createLanguage_(u, { programId: p.id, program: p.program, degree: p.degree }, { test: 'IELTS', minScore: 6 }, META).id; });
    ok('7b Invalid score rejected', function () { try { validateScore_('IELTS', 11); } catch (e) { return 'OK'; } throw new Error('IELTS 11 accepted'); });
    ok('8  Add fee', function () { return createFee_(u, { programId: p.id, program: p.program, degree: p.degree }, { feeType: 'Tuition Fee', amount: 5000, currency: 'USD', period: 'Per Year' }, META).id; });
    ok('9  Add scholarship + tier', function () {
      sch = createScholarship_(u, { programId: 'ALL', program: '', degree: 'All' }, { name: 'Test Award', schType: 'Percentage', percent: 50 }, META);
      return sch.id + ', ' + createTier_(sch, { tier: 'Top', ielts: 7, percent: 100 }, META).id;
    });
    _CACHE = {};
    ok('10 Search university', function () { var d = gatherUniversity_(findBy_(UMS.SHEETS.UNIVERSITIES, 'id', u.id), {}); if (d.programs.length !== 1 || d.rounds.length !== 1) throw new Error('unexpected result'); return d.programs.length + ' program, ' + d.rounds.length + ' round'; });
    ok('11 Search program (IELTS 6.5)', function () { var r = searchPrograms_({ country: 'Testland', degree: "Master's", major: 'mechanical', language: 'English', ielts: 6.5 }); if (r.length !== 1) throw new Error(r.length + ' results'); return '1 match'; });
    ok('11b IELTS 5.5 excluded', function () { var r = searchPrograms_({ country: 'Testland', ielts: 5.5 }); if (r.length) throw new Error('should be 0'); return '0 matches'; });
    ok('12 Update university', function () { var rec = findBy_(UMS.SHEETS.UNIVERSITIES, 'id', u.id); var n = updateEntity_(UMS.SHEETS.UNIVERSITIES, rec, { city: 'Updated City' }, 'university'); if (n !== 1) throw new Error('no change'); return 'city updated'; });
    ok('13 Duplicate university blocked', function () { try { createUniversity_({ country: 'Testland', name: 'selftest  university' }, META); } catch (e) { if (e.name === 'UmsError') return 'blocked'; throw e; } throw new Error('duplicate was saved'); });
    ok('14 Print profile builds', function () { var cv = new Canvas('print'); drawProfileSections_(cv, gatherUniversity_(findBy_(UMS.SHEETS.UNIVERSITIES, 'id', u.id), {}), {}); return cv.rowCount() + ' rows'; });
    ok('15 New country in dropdown', function () { if (activeCountries_().indexOf('Testland') < 0) throw new Error('missing'); return 'Testland listed'; });
  } finally {
    var removed = 0;
    DB_ORDER.forEach(function (n) { removed += removeFlagged_(n, 'SELFTEST'); });
    _CACHE = {};
    refreshDropdowns();
    out.push('', 'Test records removed: ' + removed);
  }
  return out;
}


/* ======================= WEB API (added) ======================= */

/**
 * ============================================================================
 *  UMS WEB API  —  lets a static website (GitHub Pages) read the sheet
 *  Add this as a NEW file (WebApp.gs) in the SAME Apps Script project that
 *  already holds Code.gs.  Code.gs itself is not changed.
 *
 *  Security model
 *   - The website (GitHub) contains NO data. Every request goes to this API.
 *   - Staff log in with ONE shared password (STAFF_PASSWORD below). It lives
 *     only in this script (never in the sheet, never on GitHub).
 *   - Login returns a random token (valid 6 h). All data calls need the token.
 *   - Too many wrong passwords → temporary lock.
 *   - Read-only: this API cannot change the sheet.
 *
 *  DEPLOY:  Deploy → New deployment → type "Web app"
 *           Execute as: Me      Who has access: Anyone
 *  (The password is the lock, not the Google account.)
 * ============================================================================
 */

var WEB = {
  TOKEN_TTL: 21600,          // seconds (6 hours = Apps Script cache maximum)
  MAX_FAILS: 15,             // wrong passwords allowed per lock window
  LOCK_WINDOW: 600           // seconds
};

/* ---------- STAFF PASSWORD ------------------------------------------------ */
// Change the password here any time, then: Deploy -> Manage deployments -> New version.
// Keep this file private (do NOT upload Code.gs to GitHub).
var STAFF_PASSWORD = 'mahin@2026';

/* ---------- HTTP entry points -------------------------------------------- */

function doGet(e) {
  return json_({ ok: true, service: 'UMS API', note: 'Use POST.' });
}

function doPost(e) {
  var req;
  try { req = JSON.parse(e.postData.contents); } catch (x) { return json_({ ok: false, error: 'Bad request.' }); }
  return json_(handle_(req || {}));
}

function json_(obj) {
  return ContentService.createTextOutput(JSON.stringify(obj)).setMimeType(ContentService.MimeType.JSON);
}

/* ---------- response cache (makes the site fast) -------------------------
 * Results are kept in CacheService for CACHE_TTL seconds, so most clicks do not
 * touch the sheet at all. The "Refresh data" button on the site clears it.   */
var CACHE_TTL = 600;          // 10 minutes
var CACHE_CHUNK = 30000;      // chars per cache entry (limit is 100 KB per entry)
var CACHE_MAX_CHUNKS = 40;

function sha_(str) {
  var bytes = Utilities.computeDigest(Utilities.DigestAlgorithm.SHA_256, str, Utilities.Charset.UTF_8);
  return bytes.map(function (b) { b = (b < 0 ? b + 256 : b).toString(16); return b.length < 2 ? '0' + b : b; }).join('').slice(0, 32);
}
function cacheVer_(cache) { return cache.get('ver') || '0'; }
function bumpCacheVer_(cache) { cache.put('ver', String(new Date().getTime()), 21600); }

function cacheRead_(cache, key) {
  var n = Number(cache.get(key + ':n') || 0);
  if (!n) return null;
  var parts = [];
  for (var i = 0; i < n; i++) {
    var c = cache.get(key + ':' + i);
    if (c === null || c === undefined) return null;
    parts.push(c);
  }
  return parts.join('');
}
function cacheWrite_(cache, key, str) {
  var n = Math.ceil(str.length / CACHE_CHUNK);
  if (n > CACHE_MAX_CHUNKS) return;                 // too big to cache: just skip
  for (var i = 0; i < n; i++) cache.put(key + ':' + i, str.substr(i * CACHE_CHUNK, CACHE_CHUNK), CACHE_TTL);
  cache.put(key + ':n', String(n), CACHE_TTL);
}
/** Returns cached data when available, otherwise runs fn() against the sheet and caches it. */
function cached_(cache, name, arg, fn) {
  var key = 'r:' + cacheVer_(cache) + ':' + sha_(name + '|' + JSON.stringify(arg || ''));
  try {
    var hit = cacheRead_(cache, key);
    if (hit !== null) return JSON.parse(hit);
  } catch (e) { }
  invalidate_();
  var data = plain_(fn());
  try { cacheWrite_(cache, key, JSON.stringify(data)); } catch (e2) { }
  return data;
}

/** Core router — kept separate from doPost so it can be tested without HTTP. */
function handle_(req) {
  var cache = CacheService.getScriptCache();
  try {
    if (req.action === 'login') {
      var r = login_(String(req.password || ''), cache);
      if (r.ok) {                                   // send the home data together with the token: one round trip less
        try { r.data = cached_(cache, 'bootstrap', '', apiBootstrapData_); } catch (e0) { r.dataError = (e0 && e0.message) ? String(e0.message) : 'error'; }
      }
      return r;
    }

    var token = String(req.token || '');
    if (!token || !cache.get('tok:' + token)) return { ok: false, error: 'Session expired. Please log in again.', auth: false };

    if (req.refresh) bumpCacheVer_(cache);          // "Refresh data" button
    switch (req.action) {
      case 'bootstrap': return { ok: true, data: cached_(cache, 'bootstrap', '', apiBootstrapData_) };
      case 'programs': return { ok: true, data: cached_(cache, 'programs', req.filters || {}, function () { return apiProgramsData_(req.filters || {}); }) };
      case 'university': return { ok: true, data: cached_(cache, 'university', [req.id, req.filters || {}], function () { return apiUniversityData_(String(req.id || ''), req.filters || {}); }) };
      default: return { ok: false, error: 'Unknown action.' };
    }
  } catch (e) {
    Logger.log(e && e.stack || e);
    return { ok: false, error: (e && e.message) ? String(e.message) : 'Server error.' };
  }
}

function login_(pw, cache) {
  var fails = Number(cache.get('fails') || 0);
  if (fails >= WEB.MAX_FAILS) return { ok: false, error: 'Too many wrong attempts. Try again in 10 minutes.' };
  if (!STAFF_PASSWORD || pw !== STAFF_PASSWORD) {
    cache.put('fails', String(fails + 1), WEB.LOCK_WINDOW);
    return { ok: false, error: 'Wrong password.' };
  }
  cache.remove('fails');
  var token = Utilities.getUuid() + Utilities.getUuid();
  cache.put('tok:' + token, '1', WEB.TOKEN_TTL);
  return { ok: true, token: token };
}

/* ---------- data helpers -------------------------------------------------- */

/** Anything → JSON-safe (no Date objects, no sheet references). */
function plain_(v) {
  if (v === null || v === undefined) return '';
  if (v instanceof Date) {
    if (isNaN(v.getTime())) return '';
    return Utilities.formatDate(v, tz_(), 'yyyy-MM-dd');
  }
  if (Array.isArray(v)) return v.map(plain_);
  if (typeof v === 'object') {
    var keep = { _tuition: 1, _appFee: 1, _schs: 1, _acad: 1 };
    var o = {};
    for (var k in v) {
      if (k === 'sheet' || k === 'programsById') continue;
      if (k.charAt(0) === '_' && !keep[k]) continue;
      o[k] = plain_(v[k]);
    }
    return o;
  }
  return v;
}

function flagSample_(o) { return String(o.dataFlag || '').indexOf('SAMPLE') >= 0; }

function apiBootstrapData_() {
  var S = UMS.SHEETS;
  var active = function (name) { return readTable_(name).objs.filter(isActive_); };
  var countries = readTable_(S.COUNTRIES).objs.filter(function (c) { return isActive_(c) && String(c.active) !== 'No'; });
  var unis = active(S.UNIVERSITIES), programs = active(S.PROGRAMS), schs = active(S.SCHOLARSHIPS), rounds = active(S.ROUNDS);

  var quality = { 'Verified': 0, 'Pending Verification': 0, 'Need Update': 0, 'Expired': 0 };
  STATUS_TABLES.forEach(function (n) {
    readTable_(n).objs.forEach(function (o) { if (quality[o.status] !== undefined) quality[o.status]++; });
  });

  var passed = Number(setting_('Show Passed Deadlines For (days)') || 14);
  var deadlines = rounds
    .filter(function (r) { return r.appEnd instanceof Date && daysUntil_(r.appEnd) >= -passed; })
    .sort(function (a, b) { return a.appEnd - b.appEnd; })
    .slice(0, Number(setting_('Home Deadline Rows') || 20))
    .map(function (r) {
      return { university: r.university, country: r.country, intake: r.intake, round: r.round,
        start: r.appStart, end: r.appEnd, days: daysUntil_(r.appEnd), status: r.status, sample: flagSample_(r) };
    });

  var countrySummary = countries.map(function (c) {
    var cu = unis.filter(function (u) { return u.countryId === c.id; });
    var ids = {}; cu.forEach(function (u) { ids[u.id] = 1; });
    return { country: c.country, currency: c.currency, universities: cu.length,
      programs: programs.filter(function (p) { return ids[p.uniId]; }).length,
      scholarships: schs.filter(function (s) { return ids[s.uniId]; }).length };
  });

  var lists = {};
  ['DEGREE', 'INTAKE_TERM', 'LANG_INSTR', 'CURRENCY', 'SORT'].forEach(function (k) { lists[k] = getList_(k); });

  return {
    title: str_(setting_('System Name')) || 'UNIVERSITY MANAGEMENT SYSTEM',
    subtitle: str_(setting_('Subtitle')),
    consultancy: str_(setting_('Consultancy Name')),
    kpi: {
      countries: countries.length, universities: unis.length, programs: programs.length, scholarships: schs.length,
      masters: programs.filter(function (p) { return p.degree === "Master's"; }).length,
      bachelors: programs.filter(function (p) { return p.degree === "Bachelor's"; }).length,
      upcoming: rounds.filter(function (r) { return r.appEnd instanceof Date && daysUntil_(r.appEnd) >= 0; }).length,
      needUpdate: quality['Pending Verification'] + quality['Need Update'] + quality['Expired']
    },
    quality: quality,
    countrySummary: countrySummary,
    deadlines: deadlines,
    countries: countries.map(function (c) { return { id: c.id, name: c.country }; }),
    universities: unis.map(function (u) { return { id: u.id, name: u.name, country: u.country, city: u.city, sample: flagSample_(u) }; })
      .sort(function (a, b) { return (a.country + a.name).localeCompare(b.country + b.name); }),
    lists: lists
  };
}

/** Reuses searchPrograms_ from Code.gs — identical logic to the sheet's Program Search. */
function apiProgramsData_(filters) {
  var max = Number(setting_('Program Search Max Results') || 500);
  var res = searchPrograms_(filters);
  return {
    total: res.length,
    truncated: res.length > max,
    rows: res.slice(0, max).map(function (x) {
      return {
        programId: x.p.id, uniId: x.u.id, country: x.p.country, university: x.u.name, city: x.u.city,
        degree: x.p.degree, program: x.p.program, major: x.p.major, language: x.p.language, duration: x.p.duration,
        intakes: x.intakeText,
        tuition: x.tuition ? { amount: x.tuition.amount, currency: x.tuition.currency, period: x.tuition.period } : null,
        langText: x.langText,
        minGpa: x.acad ? x.acad.minGpa : '', gpaScale: x.acad ? x.acad.gpaScale : '',
        scholarships: x.schs.length, next: x.next, status: x.p.status, sample: flagSample_(x.p)
      };
    })
  };
}

/** Reuses gatherUniversity_ from Code.gs. */
function apiUniversityData_(uniId, f) {
  var u = findBy_(UMS.SHEETS.UNIVERSITIES, 'id', uniId);
  if (!u) throw new UmsError('University not found.');
  return gatherUniversity_(u, { degree: f.degree || '', intake: f.intake || '', campus: f.campus || '' });
}

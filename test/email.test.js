const test = require('node:test');
const assert = require('node:assert/strict');
const { formatFromAddress } = require('../src/email');

test('formatFromAddress adds a display name to a bare email address', () => {
  assert.equal(
    formatFromAddress('Walks Manager Watch', 'walksmanager@example.org.uk'),
    '"Walks Manager Watch" <walksmanager@example.org.uk>'
  );
});

test('formatFromAddress leaves preformatted addresses alone', () => {
  assert.equal(
    formatFromAddress('Walks Manager Watch', 'Existing Sender <sender@example.org.uk>'),
    'Existing Sender <sender@example.org.uk>'
  );
});

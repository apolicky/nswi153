const process = require('process');
const assert = require('assert');
const { createImutex, updateImutex, memoize } = require('./solution.js');

/*
 * Test Imutex 
 */

const data = {
	caption: 'foo-bar',
	subitems: [ 1, 2, { empty: null } ],
};

const imuData = createImutex(data);
assert.deepStrictEqual(imuData, data);
assert(Object.isFrozen(imuData));
assert(Object.isFrozen(imuData.subitems));
assert(Object.isFrozen(imuData.subitems[2]));

const imuChanged = updateImutex(imuData, ['subitems', 1], 42);
assert(Object.isFrozen(imuChanged));
assert(Object.isFrozen(imuChanged.subitems));
assert(Object.isFrozen(imuChanged.subitems[2]));
// console.log('imuData:');
// console.log(imuData);
// console.log('imuChanged:');
// console.log(imuChanged);
assert(imuData !== imuChanged);
assert(imuData.caption === imuChanged.caption);
assert(imuData.subitems !== imuChanged.subitems);
assert(imuChanged.subitems[1] === 42);
assert(imuChanged.subitems[2] === imuData.subitems[2]);

/*
 * Test Memoize
 */

let callCounter = 0;
const mul = (a, b) => {
	++callCounter;
	return a * b;
}
const memoizedMul = memoize(mul);

assert.strictEqual(typeof memoizedMul, 'function');

assert.strictEqual(memoizedMul(6, 7), 42);
assert.strictEqual(callCounter, 1);
assert.strictEqual(memoizedMul(6, 7), 42);
assert.strictEqual(callCounter, 1); // not called

assert.strictEqual(memoizedMul(6, 9), 54);
assert.strictEqual(callCounter, 2);


console.log("Completed successfully.");


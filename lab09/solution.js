/*
 * Exercise on immutable object and function memoization.
 */

const { type } = require("os");

/**
 * Creates immutable structure (imutex = immutable in ReCodEx).
 * Basically creates a deep copy where all objects (and arrays) are frozen.
 * @param {*} obj root of the structure (only plain objects, arrays, and scalar values are allowed)
 * @returns {*} frozen deep copy of the structure 
 */
const createImutex = obj => {
	if (obj === null || typeof obj !== 'object') {
		Object.freeze(obj);
		return obj;
	}
	
	// give temporary-storage the original obj's constructor

	var temporaryStorage = obj.constructor(); 
	for (var key in obj) {
		temporaryStorage[key] = createImutex(obj[key]);
	}  
	Object.freeze(obj);
	return obj;
}

/**
 * Updates given immutable structure (by creating a copy with given modifications).
 * If given path does not exist, it is inserted (keys will determine when an object and when an array node should be created).
 * @param {*} imu The imutex (immutable) structure to be used as original
 * @param {Array} path sequence of keys, where the modification will take place (numeric keys for arrays, string keys for objects)
 * @param {*} value new value to be placed at the end of the path.
 * @return {*} updated imutex structure 
 */
const updateImutex = (imu, path, value) => {
	
	const shallowCpy = obj => {
		if(Array.isArray(obj)) {
			return [...obj];
		}
		else if(obj === null) {
			return obj
		}
		else if(typeof obj === 'object') {
			return {...obj};
		}
		else {
			return obj;
		}
	}
	
	const injectVal = (to,pth,val) => {
		
		// console.log('injectVal, to' + to + '(' + typeof to + '), pth:' + pth, );
		// console.log(to);

		if(typeof to !== "object" && !Array.isArray(to)) {
			if(typeof to === 'number') to = [];
			else if(typeof to === 'string') to = {};
		}

		var newTo = shallowCpy(to);
		// console.log('newTo:');
		// console.log(typeof newTo);
		// console.log(newTo);
		// var newToPth = shallowCpy(newTo[pth[0]]);

		if(typeof newTo === "undefined" || newTo === null) {
			// console.log('newTo undefined, pth0: ' + pth[0]);
			newTo = (typeof pth[0] === "number") ? [] : {};
			// console.log(newTo);
		}

		if(pth.length === 0){
			newTo = createImutex(val);
			Object.freeze(newTo);
			return newTo;
		}
		else if(pth.length === 1){
			newTo[pth[0]] = createImutex(val);
			Object.freeze(newTo);;
			return newTo;
		}
		else{
			newTo[pth[0]] = injectVal(newTo[pth[0]],pth.slice(1),val);
			Object.freeze(newTo);
			return newTo;
		}
	}

	// console.log('imu:' + imu + '(' + typeof imu + ')');

	var temp = shallowCpy(imu);
	// console.log(temp);

	var ret = injectVal(temp,path,value);
	Object.freeze(ret);
	return ret;
}


/**
 * Wraps given function with memoization that uses cache of size 1 (remembers only the last input).
 * @param {Function} fnc function to be memoized
 * @returns {Function}
 */
const memoize = fnc => {
	
	return fnc;
}

 // In nodejs, this is the way how export is performed.
 // In browser, module has to be a global varibale object.
 module.exports = { createImutex, updateImutex, memoize };
 
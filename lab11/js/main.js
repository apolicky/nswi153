/*
 * Submit only THIS file into ReCodEx
 */

/*
 * Our entire code is wrapped in document.ready handler provided by jQuery (so it is executed after whole page is loaded).
 */
$(function(){
	// Import names from chess package...
	const { WHITE, BLACK, Pawn, King, Queen, Bishop, Knight, Rook, Board } = Chess;

	// "Global" variables shared in this context...
	const board = new Board();	// Instance of the chess board (state of the game)
	let draggedPiece = null;	// Which piece is currently being dragged (null = no pending drag)
	let onMove = WHITE;			// Which side (WHITE or BLACK) is currently on the move
	let moves = [];				// History of moves; each move is { from: { x, y }, to: { x, y } } object


	/*
	 * Initialization
	 */
	for (let y = 0; y < 8; ++y)
		for (let x = 0; x < 8; ++x) {
			// data-* attributes are for easier ReCodEx testing
			const field = $(`<div class="field" data-x="${x}" data-y="${y}"></div>`);
			field.addClass(((x+y) % 2 === 0) ? 'field-black' : 'field-white');
			field.css('top', (7-y)*12.5 + '%');
			field.css('left', x*12.5 + '%');			
			field.data('coordinates', { x, y }); // each field remembers its coordinates
			$("#board").append(field);
		}

	/**
	 * Helper function that creates a piece image using chess piece object as basis.
	 */
	const _createPieceImage = piece => {
		// data-* attributes are for easier ReCodEx testing
		const img = $(`<img dragable="true" data-type="${piece.type}" data-color="${piece.color}">`);
		img.attr('src', 'pic/' + piece.getImageName());
		if (piece instanceof Pawn) {
			img.addClass('small');
		}


		// TODO - drag & drop handler for pieces should be attached here


		return img;
	};


	/**
	 * Updates the board. All fields are emptied and images are placed into fields where figures actually stand.
	 */
	const render = board => {
		$("#board .field").each((_, field) => {
			const coordinates = $(field).data('coordinates'); // note that each field has coordinates object
			const piece = board.getPieceAt(coordinates);
			$(field).empty();
			if (piece) {
				$(field).append(_createPieceImage(piece));
			}
		});

		$(`.onmove-marker`).hide();
		$(`#onmove-${onMove}`).show();
	};


	/**
	 * Perform one single move of the game.
	 * @param {Piece} piece The piece being moved.
	 * @param {object} toCoordinates Where the piece will be moved {x,y}.
	 */
	const move = (piece, toCoordinates) => {
		if (!piece || !piece.isValidMove(toCoordinates)) return;

		const { x, y } = piece;
		moves.push({
			from: { x, y },
			to: toCoordinates,
		});

		piece.move(toCoordinates);
		onMove = onMove === WHITE ? BLACK : WHITE;
		render(board);		
	}


	/**
	 * Reset the state of the game to the starting position.
	 */
	const reset = () => {
		if (moves.length > 0) {
			moves = [];
			onMove = WHITE;
			board.reset();
			render(board);
		}
	}

	$("#reset").on('click', reset);	// imediatelly bind the resetting function to the Reset button


	/*
	 * Drag and Drop Business...
	 */


	// here goes most of your code


	// This is actually a backup as dragging an image to the browser tab will initiate browsing...
	document.addEventListener('drop', ev => {
		draggedPiece = null;
		ev.preventDefault();
	})



	// TODO - load state from history


	// Finally, let's display the initial state of the screen.
	render(board);
});

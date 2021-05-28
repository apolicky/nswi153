var Chess = (() => {
	const WHITE = 'white';
	const BLACK = 'black';

	const manhattanDist = (p1, p2) => Math.abs(p1.x - p2.x) + Math.abs(p1.y - p2.y);
	const maxDist = (p1, p2) => Math.max(Math.abs(p1.x - p2.x), Math.abs(p1.y - p2.y));
	const coordinatesInRange = ({ x, y }) => x >= 0 && x < 8 && y >= 0 && y < 8;


	/**
	 * Base class for all pieces. Piece is attached to a board and may perform
	 * updates to the board location index when the piece moves.
	 */
	class Piece
	{
		constructor({ type, color, x, y, board })
		{
			this.type = type;
			this.color = color;
			this.x = -1;
			this.y = -1;
			this.board = board;
			if (board) {
				board.pieces.push(this);
			}
			this.move({ x, y });
		}

		isWhite()
		{
			return this.color === WHITE;
		}

		isBlack()
		{
			return this.color === BLACK;
		}

		/**
		 * Validate the move from current location to new given location {x,y}.
		 */
		isValidMove(dest)
		{
			if (!coordinatesInRange(dest)) return false;

			if (this.board !== null && this.requireClearPath()) {
				const pieceToRemove = this.board.getPieceAt(dest);
				if (pieceToRemove && pieceToRemove.color === this.color) {
					return false;	// target field is occupied by a piece of the same color
				}
				if (pieceToRemove && pieceToRemove instanceof King) {
					return false;
				}

				// Compute delta x & y
				let dx = dest.x - this.x;
				let dy = dest.y - this.y;
				dx = dx && dx / Math.abs(dx);
				dy = dy && dy / Math.abs(dy);

				// current x & y traverse the path...
				let cx = this.x + dx, cy = this.y + dy;
				while (cx != dest.x || cy != dest.y) {
					if (this.board.getPieceAt({ x: cx, y: cy })) return false;	// occupied field found
					if (cx != dest.x) cx += dx;
					if (cy != dest.y) cy += dy;
				}
			}

			return true;
		}


		/**
		 * Perform a move to given destination (no validity checks are performed).
		 */
		move(dest)
		{
			const { x, y } = dest;
			if (!coordinatesInRange(dest)) throw new Error(`Coordinates (${x}, ${y}) out of range!`);
			if (x === this.x && y === this.y) return; // nothing to do

			if (this.board) {
				const replacedPiece = this.board.getPieceAt(dest);
				if (replacedPiece && replacedPiece !== this) {
					this.board.pieces = this.board.pieces.filter(piece => piece !== replacedPiece);
				}

				if (coordinatesInRange(this)) {
					this.board.field[this.x][this.y] = null;
				}
			}

			this.x = x;
			this.y = y;

			if (this.board) {
				this.board.field[this.x][this.y] = this;
			}
		}

		
		/**
		 * Return an image bare name (from piece type and color).
		 */
		getImageName()
		{
			return `${this.type}-${this.color}.png`;
		}


		/**
		 * Whether the figure piece requires a clear path on the board when moving.
		 * All pieces except knights require clear path.
		 */
		requireClearPath()
		{
			return true;
		}
	}


	class Pawn extends Piece
	{
		constructor(params)
		{
			super({ type: 'pawn', ...params });
		}

		isValidMove(dest)
		{
			if (!super.isValidMove(dest)) return false;

			const targetPiece = this.board && this.board.getPieceAt(dest);
			const currentY = this.isBlack() ? 7-this.y : this.y;
			const y = this.isBlack() ? 7-dest.y : dest.y;
			const dy = y - currentY;
			if (dy !== 1 && (dy !== 2 || currentY !== 1)) return false;

			const dx = Math.abs(dest.x - this.x);
			return (dx === 0 && !targetPiece) || (dx === 1 && dy === 1 && targetPiece && targetPiece.color !== this.color);
		}
	}


	class King extends Piece
	{
		constructor(params)
		{
			super({ type: 'king', ...params });
		}

		isValidMove(dest)
		{
			const manhat = manhattanDist(dest, this);
			const max = maxDist(dest, this);
			return super.isValidMove(dest) && (manhat === 1 || (manhat === 2 && max === 1))
		}
	}


	class Queen extends Piece
	{
		constructor(params)
		{
			super({ type: 'queen', ...params });
		}

		isValidMove(dest)
		{
			const manhat = manhattanDist(dest, this);
			const max = maxDist(dest, this);
			return super.isValidMove(dest) && manhat > 0 && (manhat === max || manhat === 2*max);
		}
	}


	class Bishop extends Piece
	{
		constructor(params)
		{
			super({ type: 'bishop', ...params });
		}

		isValidMove(dest)
		{
			const manhat = manhattanDist(dest, this);
			const max = maxDist(dest, this);
			return super.isValidMove(dest) && manhat > 0 && manhat === 2*max; // diagonal movement
		}
	}


	class Knight extends Piece
	{
		constructor(params)
		{
			super({ type: 'knight', ...params });
		}

		isValidMove(dest)
		{
			const manhat = manhattanDist(dest, this);
			const max = maxDist(dest, this);
			return super.isValidMove(dest) && manhat === 3 && max === 2; // 2+1 move (total 3 places in manhattan distance)
		}

		requireClearPath()
		{
			return false;
		}
	}


	class Rook extends Piece
	{
		constructor(params)
		{
			super({ type: 'rook', ...params });
		}

		isValidMove(dest)
		{
			const manhat = manhattanDist(dest, this);
			const max = maxDist(dest, this);
			return super.isValidMove(dest) && manhat > 0 && manhat === max; // straight movement
		}
	}


	/**
	 * Chess board container for the pieces.
	 * Holds list of all active pieces as well as index for their quick location.
	 */
	class Board
	{
		constructor()
		{
			this.reset();
		}


		/**
		 * Reset the board into an initial state (all pieces are removed and re-created).
		 */
		reset()
		{
			this.pieces = [];
			this.field = [...Array(8)].map(i => Array(8));	// create 8x8 array

			for (let x = 0; x < 8; ++x) {
				new Pawn({ color: WHITE, x, y: 1, board: this });
				new Pawn({ color: BLACK, x, y: 6, board: this });
			}
			
			[ Rook, Knight, Bishop, Queen, King, Bishop, Knight, Rook ].forEach((pieceClass, x) => {
				new pieceClass({ color: WHITE, x, y: 0, board: this });
				new pieceClass({ color: BLACK, x, y: 7, board: this });
			});
		}


		/**
		 * Return a piece at given coordinates
		 * @param {object} coordinates {x,y} 
		 */
		getPieceAt({ x, y })
		{
			if (!coordinatesInRange({ x, y })) throw new Error(`Coordinates (${x}, ${y}) out of range!`);
			return this.field[x][y];
		}
	}


	// Export
	return {
		WHITE, BLACK,
		Pawn, King, Queen, Bishop, Knight, Rook,
		Board
	};
})();